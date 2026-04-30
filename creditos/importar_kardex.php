<?php

// 🔹 Permitir peticiones desde React
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

//  Conexión a BD
include("../config/database.php");

//  Validar que venga archivo
if (!isset($_FILES["file"])) {
    echo json_encode(["status" => "error", "msg" => "No se envió archivo"]);
    exit;
}

//  Abrir archivo CSV
$file = fopen($_FILES["file"]["tmp_name"], "r");

//  Saltar encabezado (numero_control,periodo,actividad,creditos)
fgetcsv($file);

//  Contadores (para reporte final)
$insertados = 0;
$rechazados = 0;
$no_encontrados = 0;

// Leer cada fila del CSV
while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {

    // Validar que tenga 4 columnas
    if (count($data) < 4) continue;

    // Limpiar datos
    $numero_control = trim($data[0]);
    $periodo = (int) $data[1];
    $actividad = trim($data[2]);
    $creditos = (int) $data[3];

    // Limpiar BOM (caracter invisible del primer registro)
    $numero_control = preg_replace('/^\xEF\xBB\xBF/', '', $numero_control);

    // 1. Buscar alumno por número de control
    $sqlAlumno = "SELECT id FROM alumnos WHERE numero_control = ?";
    $stmtAlumno = $conn->prepare($sqlAlumno);
    $stmtAlumno->bind_param("s", $numero_control);
    $stmtAlumno->execute();

    $resAlumno = $stmtAlumno->get_result();
    $alumno = $resAlumno->fetch_assoc();

    // Si no existe el alumno
    if (!$alumno) {
        $no_encontrados++;
        continue;
    }

    $alumno_id = $alumno["id"];

    // 2. Validar máximo 2 actividades por semestre
    $sqlCount = "SELECT COUNT(*) as total 
                 FROM registro_actividades 
                 WHERE alumno_id = ? AND periodo_id = ?";
    
    $stmtCount = $conn->prepare($sqlCount);
    $stmtCount->bind_param("ii", $alumno_id, $periodo);
    $stmtCount->execute();

    $resCount = $stmtCount->get_result();
    $rowCount = $resCount->fetch_assoc();

    if ($rowCount["total"] >= 2) {
        $rechazados++;
        continue;
    }

    // 3. Evitar duplicados exactos
    $sqlDup = "SELECT id FROM registro_actividades 
               WHERE alumno_id = ? 
               AND actividad_extra = ? 
               AND periodo_id = ?";
    
    $stmtDup = $conn->prepare($sqlDup);
    $stmtDup->bind_param("isi", $alumno_id, $actividad, $periodo);
    $stmtDup->execute();

    $resDup = $stmtDup->get_result();

    if ($resDup->num_rows > 0) {
        $rechazados++;
        continue;
    }

    //  4. Insertar actividad
    $sqlInsert = "INSERT INTO registro_actividades 
    (alumno_id, actividad_id, actividad_extra, periodo_id, creditos)
    VALUES (?, NULL, ?, ?, ?)";

    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("isii", $alumno_id, $actividad, $periodo, $creditos);

    if ($stmtInsert->execute()) {
        $insertados++;
    } else {
        $rechazados++;
    }
}

// Cerrar archivo
fclose($file);

//  Respuesta final
echo json_encode([
    "status" => "ok",
    "insertados" => $insertados,
    "rechazados" => $rechazados,
    "no_encontrados" => $no_encontrados
]);
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include("../../config/database.php");
include("../../middleware/auth.php");

$user = requireRole(['admin']);

$data = json_decode(file_get_contents("php://input"), true);

$nombre = trim($data["nombre"] ?? "");
$tipo = trim($data["tipo"] ?? "");
$promotor = trim($data["promotor"] ?? "");
$horarios = $data["horarios"] ?? [];

if ($nombre === "" || $tipo === "" || $promotor === "") {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Faltan datos del taller"
    ]);
    exit;
}

if (!in_array($tipo, ["deportivo", "cultural", "civico"])) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Tipo de taller inválido"
    ]);
    exit;
}

if (!is_array($horarios) || count($horarios) === 0) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Agrega al menos un horario"
    ]);
    exit;
}

// Obtener ciclo activo
$resCiclo = $conn->query("
    SELECT id 
    FROM ciclos 
    WHERE estado = 'activo' 
    LIMIT 1
");

if ($resCiclo->num_rows === 0) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "No hay ciclo activo"
    ]);
    exit;
}

$ciclo = $resCiclo->fetch_assoc();
$ciclo_id = intval($ciclo["id"]);

$conn->begin_transaction();

try {

    // Crear taller
    $stmt = $conn->prepare("
        INSERT INTO talleres
        (
            ciclo_id,
            nombre,
            tipo,
            promotor,
            estado
        )
        VALUES (?, ?, ?, ?, 'activo')
    ");

    $stmt->bind_param(
        "isss",
        $ciclo_id,
        $nombre,
        $tipo,
        $promotor
    );

    if (!$stmt->execute()) {
        throw new Exception("Error al crear taller: " . $stmt->error);
    }

    $taller_id = $conn->insert_id;

    // Crear horarios
    foreach ($horarios as $h) {

        $dia_semana = intval($h["dia_semana"] ?? 0);
        $hora_inicio = trim($h["hora_inicio"] ?? "");
        $hora_fin = trim($h["hora_fin"] ?? "");
        $cupo_maximo = intval($h["cupo_maximo"] ?? 0);
        $espacio = trim($h["espacio"] ?? "");

        if (
            $dia_semana < 1 ||
            $dia_semana > 5 ||
            $hora_inicio === "" ||
            $hora_fin === "" ||
            $cupo_maximo <= 0 ||
            $espacio === ""
        ) {
            throw new Exception("Hay horarios incompletos o inválidos");
        }

        if ($hora_inicio >= $hora_fin) {
            throw new Exception("La hora final debe ser mayor que la hora inicial");
        }

        $stmtHorario = $conn->prepare("
            INSERT INTO horarios
            (
                taller_id,
                dia_semana,
                hora_inicio,
                hora_fin,
                cupo_maximo,
                espacio,
                estado
            )
            VALUES (?, ?, ?, ?, ?, ?, 'activo')
        ");

        $stmtHorario->bind_param(
            "iissis",
            $taller_id,
            $dia_semana,
            $hora_inicio,
            $hora_fin,
            $cupo_maximo,
            $espacio
        );

        if (!$stmtHorario->execute()) {
            throw new Exception("Error al crear horario: " . $stmtHorario->error);
        }
    }

    $conn->commit();

    echo json_encode([
        "ok" => true,
        "mensaje" => "Taller y horarios creados correctamente",
        "taller_id" => $taller_id
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "ok" => false,
        "mensaje" => $e->getMessage()
    ]);
}
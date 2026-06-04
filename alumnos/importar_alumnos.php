<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include("../config/database.php");

if ($_FILES["archivo"]["name"]) {

    $filename = $_FILES["archivo"]["tmp_name"];

    $generacion_id = intval($_POST["generacion_id"]);
    $carrera_id = intval($_POST["carrera_id"]);
    $periodo_id = intval($_POST["periodo_id"]);

    $file = fopen($filename, "r");

    // Saltar encabezado
    fgetcsv($file);

    $insertados = 0;
    $rechazados = 0;

    while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {

        $numero_control = trim($data[0]);
        $nombre = trim($data[1]);

        if (!$numero_control || !$nombre) {
            continue;
        }

        // evitar duplicados
        $stmtCheck = $conn->prepare("
            SELECT id
            FROM alumnos
            WHERE numero_control=?
        ");

        $stmtCheck->bind_param(
            "s",
            $numero_control
        );

        $stmtCheck->execute();

        $res = $stmtCheck->get_result();

        if($res->num_rows > 0){
            $rechazados++;
            continue;
        }

        $sql = "
        INSERT INTO alumnos
        (
            numero_control,
            nombre,
            generacion_id,
            carrera_id,
            periodo_id
        )
        VALUES (?, ?, ?, ?, ?)
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssiii",
            $numero_control,
            $nombre,
            $generacion_id,
            $carrera_id,
            $periodo_id
        );

        if ($stmt->execute()) {
            $insertados++;
        } else {
            $rechazados++;
        }
    }

    fclose($file);

    echo json_encode([
        "status" => "ok",
        "insertados" => $insertados,
        "rechazados" => $rechazados
    ]);

} else {

    echo json_encode([
        "status" => "error"
    ]);

}
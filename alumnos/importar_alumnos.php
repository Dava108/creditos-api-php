<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include("../config/database.php");

if ($_FILES["archivo"]["name"]) {

    $filename = $_FILES["archivo"]["tmp_name"];
    $generacion_id = $_POST["generacion_id"];
    $carrera_id = $_POST["carrera_id"];

    $file = fopen($filename, "r");

    // Saltar encabezado
    fgetcsv($file);

    $insertados = 0;

    while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {

        $numero_control = $data[0];
        $nombre = $data[1];

        if (!$numero_control || !$nombre) continue;

        $sql = "INSERT INTO alumnos (numero_control, nombre, generacion_id, carrera_id)
                VALUES (?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $numero_control, $nombre, $generacion_id, $carrera_id);

        if ($stmt->execute()) {
            $insertados++;
        }
    }

    fclose($file);

    echo json_encode([
        "status" => "ok",
        "insertados" => $insertados
    ]);

} else {
    echo json_encode(["status" => "error"]);
}
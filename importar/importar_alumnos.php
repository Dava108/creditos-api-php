<?php

header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST");
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
include "../config/database.php";

if(isset($_FILES["archivo"])){

    $archivo = fopen($_FILES["archivo"]["tmp_name"], "r");

    // saltar encabezado
    fgetcsv($archivo);

    while(($datos = fgetcsv($archivo, 1000, ",")) !== FALSE){

        $numero_control = $datos[0];
        $nombre = $datos[1];
        $generacion_id = $_POST["generacion_id"];

        $stmt = $conn->prepare("
        INSERT INTO alumnos (numero_control, nombre, generacion_id)
        VALUES (?, ?, ?)
        ");

        $stmt->bind_param("ssi", $numero_control, $nombre, $generacion_id);
        $stmt->execute();
    }

    echo json_encode(["status"=>"importado"]);
}
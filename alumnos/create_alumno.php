<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST");


include "../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);

$numero_control = $data["numero_control"];
$nombre = $data["nombre"];
$generacion_id = $data["generacion_id"];

$stmt = $conn->prepare("
INSERT INTO alumnos (numero_control, nombre, generacion_id)
VALUES (?, ?, ?)
");

$stmt->bind_param("ssi", $numero_control, $nombre, $generacion_id);

if($stmt->execute()){
    echo json_encode(["status" => "ok"]);
}else{
    echo json_encode(["status" => "error"]);
}
<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

include("../config/database.php");

$data = json_decode(file_get_contents("php://input"), true);

$nombre = $data["nombre"];
$numero = $data["numero_control"];
$rol = $data["rol"];
$generacion = 999;
$carrera = 999;
//  password = numero_control
$password = password_hash($numero, PASSWORD_DEFAULT);

$stmt = $conn->prepare("
  INSERT INTO alumnos (nombre, numero_control, rol, password, generacion_id, carrera_id) 
  VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("ssssii", $nombre, $numero, $rol, $password, $generacion, $carrera);


$stmt->execute();

echo json_encode(["status" => "ok"]);
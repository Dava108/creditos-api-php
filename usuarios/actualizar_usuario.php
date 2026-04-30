<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

include("../config/database.php");

$data = json_decode(file_get_contents("php://input"), true);

$id = $data["id"];
$nombre = $data["nombre"];
$rol = $data["rol"];

$stmt = $conn->prepare("UPDATE alumnos SET nombre=?, rol=? WHERE id=?");
$stmt->bind_param("ssi", $nombre, $rol, $id);
$stmt->execute();

echo json_encode(["status" => "ok"]);
<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

include("../config/database.php");

$data = json_decode(file_get_contents("php://input"), true);

$id = $data["id"];
$password_actual = $data["password_actual"];
$password_nuevo = $data["password_nuevo"];

// obtener usuario
$stmt = $conn->prepare("SELECT password FROM alumnos WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(["status" => "error", "message" => "Usuario no encontrado"]);
    exit;
}

// validar password actual
if (!password_verify($password_actual, $user["password"])) {
    echo json_encode(["status" => "error", "message" => "Contraseña incorrecta"]);
    exit;
}

// guardar nueva
$hash = password_hash($password_nuevo, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE alumnos SET password=? WHERE id=?");
$stmt->bind_param("si", $hash, $id);
$stmt->execute();

echo json_encode(["status" => "ok"]);
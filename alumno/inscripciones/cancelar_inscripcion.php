<?php

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("../../config/database.php");
include("../../middleware/auth.php");

$user = requireRole(['alumno']);

$alumno_id = $user['id'];

$data = json_decode(file_get_contents("php://input"), true);

$horario_id = isset($data["horario_id"])
    ? intval($data["horario_id"])
    : 0;

if ($horario_id <= 0) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Datos inválidos"
    ]);
    exit;
}

$stmt = $conn->prepare("
    DELETE FROM inscripciones
    WHERE alumno_id = ? AND horario_id = ?
");

$stmt->bind_param("ii", $alumno_id, $horario_id);

if ($stmt->execute()) {

    echo json_encode([
        "ok" => true
    ]);

} else {

    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al cancelar"
    ]);
}
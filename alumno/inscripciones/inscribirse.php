<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");



header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");

include("../../config/database.php");
include("../../helpers/validaciones.php");
include("../../middleware/auth.php");



$user = requireRole(['alumno']);

$data = json_decode(file_get_contents("php://input"), true);

// 🔐 DATOS

$alumno_id = $user['id'];

$horario_id = isset($data["horario_id"]) ? intval($data["horario_id"]) : 0;

// 🔍 VALIDACIÓN BÁSICA
if ($alumno_id <= 0 || $horario_id <= 0) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Datos inválidos"
    ]);
    exit;
}

// 🔥 VALIDACIONES DE NEGOCIO
$validacion = validarInscripcion($alumno_id, $horario_id, $conn);

if (!$validacion["ok"]) {
    echo json_encode($validacion);
    exit;
}

// 🔄 INSERT
$stmt = $conn->prepare("
    INSERT INTO inscripciones (alumno_id, horario_id)
    VALUES (?, ?)
");

$stmt->bind_param("ii", $alumno_id, $horario_id);

if ($stmt->execute()) {
    echo json_encode([
        "ok" => true,
        "mensaje" => "Inscripción realizada correctamente"
    ]);
} else {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error SQL",
        "error" => $stmt->error
    ]);
}
<?php
//  CORS (OBLIGATORIO)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

//  Manejo preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");

include("../config/database.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

//  Leer JSON
$data = json_decode(file_get_contents("php://input"), true);

//  Obtener datos
$taller_id = $data['taller_id'] ?? null;
$dia = $data['dia_semana'] ?? null;
$inicio = $data['hora_inicio'] ?? null;
$fin = $data['hora_fin'] ?? null;

//  Validación
if (!$taller_id || !$dia || !$inicio || !$fin) {
    echo json_encode([
        "status" => "error",
        "msg" => "Datos incompletos"
    ]);
    exit;
}

// (Opcional pero recomendado)
if ($inicio >= $fin) {
    echo json_encode([
        "status" => "error",
        "msg" => "Hora inválida"
    ]);
    exit;
}

// ✅ Insertar
$sql = "INSERT INTO horarios (taller_id, dia_semana, hora_inicio, hora_fin)
        VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "msg" => $conn->error
    ]);
    exit;
}

$stmt->bind_param("iiss", $taller_id, $dia, $inicio, $fin);

if ($stmt->execute()) {
    echo json_encode(["status" => "ok"]);
} else {
    echo json_encode([
        "status" => "error",
        "msg" => $stmt->error
    ]);
}
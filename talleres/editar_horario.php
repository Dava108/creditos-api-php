<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
error_reporting(0);
ini_set('display_errors', 0);

//  manejar preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
include("../config/database.php");

// ✅ Leer JSON
$data = json_decode(file_get_contents("php://input"), true);

$id = $data['horario_id'] ?? null;
$inicio = $data['hora_inicio'] ?? null;
$fin = $data['hora_fin'] ?? null;

// ✅ Validación básica
if (!$id || !$inicio || !$fin) {
    echo json_encode(["status" => "error", "msg" => "Datos incompletos"]);
    exit;
}

// obtener horario actual
$sql = "SELECT * FROM horarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$h = $stmt->get_result()->fetch_assoc();

if (!$h) {
    echo json_encode(["status" => "error", "msg" => "No existe"]);
    exit;
}

// validar traslape
$sql = "SELECT * FROM horarios
WHERE taller_id = ?
AND dia_semana = ?
AND id != ?
AND (? < hora_fin AND ? > hora_inicio)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiss", $h['taller_id'], $h['dia_semana'], $id, $inicio, $fin);
$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(["status" => "error", "msg" => "Conflicto"]);
    exit;
}

// actualizar
$sql = "UPDATE horarios SET hora_inicio=?, hora_fin=? WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $inicio, $fin, $id);
$stmt->execute();

echo json_encode(["status" => "ok"]);
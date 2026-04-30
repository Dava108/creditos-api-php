<?php
// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");

//  manejar preflight (IMPORTANTE)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// conexión BD
include("../config/database.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ LEER JSON
$data = json_decode(file_get_contents("php://input"), true);

// ✅ VALIDAR
if (!$data) {
    echo json_encode(["status" => "error", "msg" => "No data"]);
    exit;
}

$nombre = $data['nombre'] ?? null;
$tipo = $data['tipo'] ?? null;
$cupo = $data['cupo_max'] ?? null;
$ciclo = $data['ciclo_id'] ?? null;
$creado = $data['creado_por'] ?? null;

// ✅ VALIDAR CAMPOS
if (!$nombre || !$tipo || !$cupo) {
    echo json_encode(["status" => "error", "msg" => "Campos incompletos"]);
    exit;
}

// ✅ INSERT
$sql = "INSERT INTO talleres (nombre, tipo, cupo_max, ciclo_id, creado_por)
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["status" => "error", "msg" => $conn->error]);
    exit;
}

$stmt->bind_param("ssiii", $nombre, $tipo, $cupo, $ciclo, $creado);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "ok",
        "id" => $conn->insert_id
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "msg" => $stmt->error
    ]);
}
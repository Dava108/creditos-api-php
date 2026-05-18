<?php

 


header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");

header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// 🔥 MUY IMPORTANTE (para preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");

include "../../config/database.php";
include("../../middleware/auth.php");

$user = requireRole(['admin']);

$data = json_decode(file_get_contents("php://input"), true);

$nombre = $data['nombre'];
$tipo = $data['tipo'];
$promotor = $data['promotor'] ?? null;

// ciclo activo
$sql = "SELECT id FROM ciclos WHERE estado = 'activo' LIMIT 1";
$res = $conn->query($sql);

if ($res->num_rows === 0) {
    echo json_encode(["ok" => false, "mensaje" => "No hay ciclo activo"]);
    exit;
}

$ciclo = $res->fetch_assoc();

$stmt = $conn->prepare("
    INSERT INTO talleres (ciclo_id, nombre, tipo, promotor)
    VALUES (?, ?, ?, ?)
");

$stmt->bind_param("isss", $ciclo['id'], $nombre, $tipo, $promotor);

echo json_encode([
    "ok" => $stmt->execute()
]);
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

$id = $data['id'];
$estado = $data['estado']; // activo | inactivo

$stmt = $conn->prepare("
    UPDATE talleres SET estado = ? WHERE id = ?
");

$stmt->bind_param("si", $estado, $id);

echo json_encode(["ok" => $stmt->execute()]);
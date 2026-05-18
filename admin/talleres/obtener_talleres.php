<?php

session_start();

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include("../../config/database.php");
include("../../middleware/auth.php");

$user = requireRole(['admin']);

$sql = "
SELECT 
    t.*,
    c.nombre AS ciclo_nombre
FROM talleres t
INNER JOIN ciclos c 
    ON t.ciclo_id = c.id
WHERE c.estado = 'activo'
ORDER BY t.id DESC
";

$res = $conn->query($sql);

$data = [];

while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
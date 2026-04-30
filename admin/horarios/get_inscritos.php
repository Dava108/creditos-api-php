<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");

include("../../config/database.php");

$horario_id = isset($_GET['id_horario']) ? intval($_GET['id_horario']) : 0;

if ($horario_id <= 0) {
    echo json_encode([]);
    exit;
}

$sql = "
SELECT 
    a.id,
    a.nombre,
    a.numero_control
FROM inscripciones i
INNER JOIN alumnos a ON i.alumno_id = a.id
WHERE i.horario_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $horario_id);
$stmt->execute();

$res = $stmt->get_result();

$data = [];

while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
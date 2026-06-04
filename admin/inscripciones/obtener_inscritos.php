<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");

header("Content-Type: application/json");
// 🔥 MUY IMPORTANTE (para preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


include "../../config/database.php";
include("../../middleware/auth.php");

$user = requireRole(['admin']);

$horario_id = $_GET['horario_id'];


$sql = "
SELECT 
  a.id,
  a.nombre,
  a.numero_control,
  c.nombre AS carrera,
  i.id AS inscripcion_id,
  i.estado
FROM inscripciones i
INNER JOIN alumnos a ON i.alumno_id = a.id
INNER JOIN carreras c ON a.carrera_id = c.id
WHERE i.horario_id = ?
AND i.estado='activa'
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
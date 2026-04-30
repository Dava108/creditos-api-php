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

$alumno_id = isset($_GET['alumno_id']) ? intval($_GET['alumno_id']) : 0;

if ($alumno_id <= 0) {
    echo json_encode([]);
    exit;
}

// ciclo activo
$ciclo = $conn->query("SELECT id FROM ciclos WHERE estado='activo' LIMIT 1");

if ($ciclo->num_rows === 0) {
    echo json_encode([]);
    exit;
}

$ciclo_id = $ciclo->fetch_assoc()['id'];

$sql = "
SELECT 
    i.horario_id,
    t.nombre,
    t.tipo,
    h.dia_semana,
    h.hora_inicio,
    h.hora_fin
FROM inscripciones i
INNER JOIN horarios h ON i.horario_id = h.id
INNER JOIN talleres t ON h.taller_id = t.id
WHERE i.alumno_id = ?
AND t.ciclo_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $alumno_id, $ciclo_id);
$stmt->execute();

$res = $stmt->get_result();

$data = [];

while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
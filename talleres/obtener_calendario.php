<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

//  manejar preflight (MUY IMPORTANTE)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include("../config/database.php");
$sql = "
SELECT 
  t.id AS taller_id,
  t.nombre,
  t.tipo,
  t.cupo_max,
  h.id AS horario_id,
  h.dia_semana,
  h.hora_inicio,
  h.hora_fin,
  COUNT(i.id) as inscritos
FROM talleres t
LEFT JOIN horarios h ON h.taller_id = t.id
LEFT JOIN inscripciones i ON i.horario_id = h.id
GROUP BY t.id, h.id
ORDER BY t.id, h.dia_semana, h.hora_inicio;
";
$res = $conn->query($sql);

$data = [];

while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
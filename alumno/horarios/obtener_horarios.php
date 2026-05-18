<?php

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");


header("Content-Type: application/json");


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


include("../../config/database.php");
include("../../middleware/auth.php");

$user = requireRole(['alumno']);

$alumno_id = $user['id'];

$taller_id = isset($_GET['taller_id']) ? intval($_GET['taller_id']) : 0;

if ($taller_id <= 0) {
    echo json_encode([]);
    exit;
}

$sql = "
SELECT 
  h.id,
  h.dia_semana,
  CASE 
    WHEN h.dia_semana = 1 THEN 'Lunes'
    WHEN h.dia_semana = 2 THEN 'Martes'
    WHEN h.dia_semana = 3 THEN 'Miércoles'
    WHEN h.dia_semana = 4 THEN 'Jueves'
    WHEN h.dia_semana = 5 THEN 'Viernes'
  END AS dia_nombre,

  h.hora_inicio,
  h.hora_fin,
  h.espacio,

  COUNT(i.id) AS inscritos,
  (h.cupo_maximo + 5) AS cupo_total,

  CASE 
    WHEN ia.id IS NOT NULL THEN 1
    ELSE 0
  END AS inscrito

FROM horarios h

LEFT JOIN inscripciones i 
  ON h.id = i.horario_id

LEFT JOIN inscripciones ia 
  ON ia.horario_id = h.id 
  AND ia.alumno_id = ?

INNER JOIN talleres t 
  ON h.taller_id = t.id

INNER JOIN ciclos c 
  ON t.ciclo_id = c.id

WHERE 
  h.taller_id = ?
  AND c.estado = 'activo'
  AND h.estado = 'activo'

GROUP BY h.id
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $alumno_id, $taller_id);
$stmt->execute();

$res = $stmt->get_result();

$data = [];

while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
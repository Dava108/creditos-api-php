<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include("../../config/database.php");

$sql = "
SELECT 
  t.id,
  t.nombre,
  t.tipo,
  t.promotor
FROM talleres t
INNER JOIN ciclos c ON t.ciclo_id = c.id
WHERE c.estado = 'activo'
AND t.estado = 'activo'
";

$res = $conn->query($sql);

$data = [];

while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
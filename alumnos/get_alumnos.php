<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST");

include "../config/database.php";

$sql = "
SELECT 
  a.id,
  a.numero_control,
  a.nombre,
  g.nombre AS generacion,
  c.nombre AS carrera_nombre
FROM alumnos a
JOIN generaciones g ON a.generacion_id = g.id
JOIN carreras c ON a.carrera_id = c.id
";

$result = $conn->query($sql);

$alumnos = [];

while ($row = $result->fetch_assoc()) {
    $alumnos[] = $row;
}

echo json_encode($alumnos);
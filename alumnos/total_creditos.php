<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST");
header("Content-Type: application/json");

include "../config/database.php";

$alumno_id = $_GET["alumno_id"];

$sql = "
SELECT 
alumnos.nombre,
SUM(registro_actividades.creditos) AS total_creditos
FROM registro_actividades
JOIN alumnos 
ON registro_actividades.alumno_id = alumnos.id
WHERE alumnos.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $alumno_id);
$stmt->execute();

$result = $stmt->get_result();

$data = $result->fetch_assoc();

echo json_encode($data);
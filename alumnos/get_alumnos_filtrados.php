<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include("../config/database.php");

$generacion = $_GET["generacion_id"] ?? null;
$carrera = $_GET["carrera_id"] ?? null;

if (!$generacion || !$carrera) {
    echo json_encode([]);
    exit;
}
//QUERY
$stmt = $conn->prepare("
    SELECT 
        a.id,
        a.numero_control,
        a.nombre,
        g.nombre AS generacion,
        c.nombre AS carrera_nombre
    FROM alumnos a
    JOIN generaciones g ON a.generacion_id = g.id
    JOIN carreras c ON a.carrera_id = c.id
    WHERE a.generacion_id = ? AND a.carrera_id = ?
");

if (!$stmt) {
    echo json_encode(["error" => $conn->error]);
    exit;
}

$stmt->bind_param("ii", $generacion, $carrera);

$stmt->execute();

$result = $stmt->get_result();

$rows = [];

while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

echo json_encode($rows);
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include("../config/database.php");

$alumno_id = $_GET["alumno_id"] ?? null;

if (!$alumno_id) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT ra.id, ra.periodo_id, ra.creditos,
        COALESCE(a.nombre, ra.actividad_extra) AS actividad
        FROM registro_actividades ra
        LEFT JOIN actividades a ON ra.actividad_id = a.id
        WHERE ra.alumno_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $alumno_id);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

echo json_encode($rows);
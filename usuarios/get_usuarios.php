<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include("../config/database.php");

$result = $conn->query("SELECT id, nombre, numero_control, rol
 FROM alumnos
 WHERE rol IN ('admin','promotor')
 ");

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
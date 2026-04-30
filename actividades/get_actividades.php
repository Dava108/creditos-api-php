<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST");


include("../config/database.php");

$sql = "SELECT * FROM actividades";

$result = $conn->query($sql);

$actividades = [];

while($row = $result->fetch_assoc()){
    $actividades[] = $row;
}

echo json_encode($actividades);
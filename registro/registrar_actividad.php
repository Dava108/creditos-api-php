<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST");


include "../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);

$alumno_id = $data["alumno_id"];
$actividad_id = $data["actividad_id"];
$periodo_id = $data["periodo_id"];
$creditos = $data["creditos"];

$sql = "INSERT INTO registro_actividades 
(alumno_id, actividad_id, periodo_id, creditos)
VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $alumno_id, $actividad_id, $periodo_id, $creditos);

if($stmt->execute()){
    echo json_encode(["status"=>"ok"]);
}else{
    echo json_encode(["status"=>"error"]);
}
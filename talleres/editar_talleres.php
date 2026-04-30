<?php
// Permitir cualquier origen (o cambia * por http://localhost:3000)
header("Access-Control-Allow-Origin: *");
// Permitir los métodos que usa React
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
// ESTA ES LA LÍNEA CLAVE: Permitir el encabezado Content-Type
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Manejar la petición "preflight" (OPTIONS) que hace el navegador automáticamente

header("Content-Type: application/json");
error_reporting(0);
ini_set('display_errors', 0);


if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

include("../config/database.php");

$data = json_decode(file_get_contents("php://input"), true);

$id = $data["horario_id"];
$inicio = $data["hora_inicio"];
$fin = $data["hora_fin"];

$query = "UPDATE horarios SET hora_inicio=?, hora_fin=? WHERE id=?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssi", $inicio, $fin, $id);

if($stmt->execute()){
    echo json_encode(["status"=>"ok"]);
}else{
    echo json_encode(["status"=>"error"]);
}
<?php
// Permitir cualquier origen (o cambia * por http://localhost:3000)
header("Access-Control-Allow-Origin: *");
// Permitir los métodos que usa React
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
// ESTA ES LA LÍNEA CLAVE: Permitir el encabezado Content-Type
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json");
error_reporting(0);
ini_set('display_errors', 0);

// Manejar la petición "preflight" (OPTIONS) que hace el navegador automáticamente
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

include("../config/database.php");

$data = json_decode(file_get_contents("php://input"), true);

$id = $data["taller_id"];
$nombre = $data["nombre"];
$tipo = $data["tipo"];
$cupo = $data["cupo_max"];

$query = "UPDATE talleres 
SET nombre=?, tipo=?, cupo_max=? 
WHERE id=?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ssii", $nombre, $tipo, $cupo, $id);

if($stmt->execute()){
    echo json_encode(["status"=>"ok"]);
}else{
    echo json_encode(["status"=>"error"]);
}
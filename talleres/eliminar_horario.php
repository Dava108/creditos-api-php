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


$id = $_POST['id'];

// opcional: validar si tiene inscritos
$sql = "SELECT COUNT(*) as total FROM inscripciones WHERE horario_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if ($res['total'] > 0) {
    echo json_encode(["status" => "error", "msg" => "Tiene inscritos"]);
    exit;
}

$sql = "DELETE FROM horarios WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

echo json_encode(["status" => "ok"]);
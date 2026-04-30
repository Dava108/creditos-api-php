<?php

//  Mostrar errores (temporal para debug)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include("../config/database.php");

// Leer JSON
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "no data"]);
    exit;
}

// Variables
$id = $data["id"];
$actividad_id = !empty($data["actividad_id"]) ? $data["actividad_id"] : null;
$actividad_extra = $data["actividad_extra"] ?? "";
$creditos = $data["creditos"] ?? 0;

// Query
$stmt = $conn->prepare("
    UPDATE registro_actividades
    SET actividad_id=?, actividad_extra=?, creditos=?
    WHERE id=?
");

$stmt->bind_param("isii", $actividad_id, $actividad_extra, $creditos, $id);

//Ejecutar
if ($stmt->execute()) {
    echo json_encode(["status" => "ok"]);
} else {
    echo json_encode([
        "status" => "error",
        "error" => $stmt->error
    ]);
}
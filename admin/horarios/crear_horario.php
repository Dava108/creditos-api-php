<?php
// Headers con comillas


header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Manejo de Preflight (peticiones OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");

include "../../config/database.php";
include "../../helpers/horarios.php";

$data = json_decode(file_get_contents("php://input"), true);

// Extraer variables del JSON recibido para que existan
$taller_id  = $data['taller_id'] ?? null;
$dia_semana = $data['dia_semana'] ?? null;
$hora_inicio = $data['hora_inicio'] ?? null;
$hora_fin    = $data['hora_fin'] ?? null;

// Validación (asegúrate que validarHorario reciba estas variables)
$validacion = validarHorario($taller_id, $dia_semana, $hora_inicio, $hora_fin, $conn);

if (!$validacion['ok']) {
    echo json_encode($validacion);
    exit;
}

// Preparar el INSERT
$stmt = $conn->prepare("INSERT INTO horarios (taller_id, dia_semana, hora_inicio, hora_fin, cupo_maximo, espacio) VALUES (?, ?, ?, ?, ?, ?)");

$stmt->bind_param(
    "iissis", 
    $data['taller_id'], 
    $data['dia_semana'], 
    $data['hora_inicio'], 
    $data['hora_fin'], 
    $data['cupo_maximo'], 
    $data['espacio']
);

echo json_encode(["ok" => $stmt->execute()]);

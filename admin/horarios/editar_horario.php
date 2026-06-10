<?php

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


include "../../config/database.php";
include "../../helpers/horarios.php";

$data = json_decode(file_get_contents("php://input"), true);

// Validar datos mínimos
if (
    !isset($data['id']) ||
    !isset($data['dia_semana']) ||
    !isset($data['hora_inicio']) ||
    !isset($data['hora_fin'])
) {
    echo json_encode(["ok" => false, "mensaje" => "Datos incompletos"]);
    exit;
}

$id = intval($data['id']);

// 🔍 Obtener taller_id del horario
$stmt = $conn->prepare("SELECT taller_id FROM horarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(["ok" => false, "mensaje" => "Horario no encontrado"]);
    exit;
}

$taller = $res->fetch_assoc();
$taller_id = $taller['taller_id'];

//  VALIDACIÓN DE EMPALME / DUPLICADO
$validacion = validarHorario(
    $taller_id,
    $data['dia_semana'],
    $data['hora_inicio'],
    $data['hora_fin'],
    $conn,
    $id //  ignorar el mismo horario
);

if (!$validacion["ok"]) {
    echo json_encode($validacion);
    exit;
}

//  UPDATE
$stmt = $conn->prepare("
    UPDATE horarios 
    SET dia_semana=?, hora_inicio=?, hora_fin=?, cupo_maximo=?, espacio=?
    WHERE id=?
");

$stmt->bind_param(
    "issisi",
    $data['dia_semana'],
    $data['hora_inicio'],
    $data['hora_fin'],
    $data['cupo_maximo'],
    $data['espacio'],
    $id
);

if ($stmt->execute()) {
    echo json_encode(["ok" => true]);
} else {
    echo json_encode(["ok" => false, "mensaje" => "Error al actualizar"]);
}
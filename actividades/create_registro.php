<?php
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include("../config/database.php");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "no data"]);
    exit;
}

$alumno_id = $data["alumno_id"];
$actividad_id = !empty($data["actividad_id"]) ? $data["actividad_id"] : null;
$actividad_extra = $data["actividad_extra"] ?? "";
$periodo_id = $data["periodo_id"];
$creditos = $data["creditos"] ?? 0;

// Limitar máximo 2 actividades por semestre
$stmt_check = $conn->prepare("SELECT COUNT(*) AS total FROM registro_actividades WHERE alumno_id=? AND periodo_id=?");
$stmt_check->bind_param("ii", $alumno_id, $periodo_id);
$stmt_check->execute();
$result = $stmt_check->get_result()->fetch_assoc();
if ($result['total'] >= 2) {
    echo json_encode(["status" => "error", "error" => "Ya hay 2 actividades registradas en este semestre"]);
    exit;
}

// Insertar
$stmt = $conn->prepare("
    INSERT INTO registro_actividades
    (alumno_id, actividad_id, actividad_extra, periodo_id, creditos)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param("iisii", $alumno_id, $actividad_id, $actividad_extra, $periodo_id, $creditos);

if ($stmt->execute()) {
    echo json_encode(["status" => "ok"]);
} else {
    echo json_encode(["status" => "error", "error" => $stmt->error]);
}
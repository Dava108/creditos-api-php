<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

//  manejar preflight (MUY IMPORTANTE)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include("../config/database.php");


$alumno = $_POST['alumno_id'];
$horario = $_POST['horario_id'];

// obtener horario
$sql = "SELECT * FROM horarios WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $horario);
$stmt->execute();
$h = $stmt->get_result()->fetch_assoc();

// validar choque
$sql = "SELECT h.* FROM inscripciones i
JOIN horarios h ON i.horario_id = h.id
WHERE i.alumno_id = ?
AND h.dia_semana = ?
AND (? < h.hora_fin AND ? > h.hora_inicio)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiss", $alumno, $h['dia_semana'], $h['hora_inicio'], $h['hora_fin']);
$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(["status" => "error", "msg" => "Choque de horario"]);
    exit;
}

// validar cupo
$sql = "SELECT COUNT(*) as total FROM inscripciones WHERE horario_id=? AND estado='activo'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $horario);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];

// obtener cupo
$sql = "SELECT t.cupo_max FROM talleres t JOIN horarios h ON h.taller_id=t.id WHERE h.id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $horario);
$stmt->execute();
$cupo = $stmt->get_result()->fetch_assoc()['cupo_max'];

if ($total >= $cupo) {
    echo json_encode(["status" => "error", "msg" => "Cupo lleno"]);
    exit;
}

// insertar
$sql = "INSERT INTO inscripciones (alumno_id, horario_id) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $alumno, $horario);
$stmt->execute();

echo json_encode(["status" => "ok"]);
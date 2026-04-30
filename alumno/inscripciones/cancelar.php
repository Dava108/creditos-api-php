<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");

include("../../config/database.php");
include("../../middleware/auth.php");

// 🔐 Usuario desde sesión (NO desde frontend)
$user = requireRole(['alumno']);
$alumno_id = $user['id'];

$data = json_decode(file_get_contents("php://input"), true);
$horario_id = isset($data["horario_id"]) ? intval($data["horario_id"]) : 0;

if ($horario_id <= 0) {
    echo json_encode(["ok" => false, "mensaje" => "Datos invalidos"]);
    exit;
}

try {

    $conn->begin_transaction();

    // 🟡 Validar ciclo activo y periodo
    $sql = "SELECT id, fecha_inicio, fecha_fin 
            FROM ciclos 
            WHERE estado = 'activo' 
            LIMIT 1";
    $res = $conn->query($sql);

    if ($res->num_rows === 0) {
        $conn->rollback();
        echo json_encode(["ok" => false, "mensaje" => "No hay ciclo activo"]);
        exit;
    }

    $ciclo = $res->fetch_assoc();
    $hoy = date('Y-m-d');

    if ($hoy < $ciclo['fecha_inicio'] || $hoy > $ciclo['fecha_fin']) {
        $conn->rollback();
        echo json_encode(["ok" => false, "mensaje" => "Fuera del periodo"]);
        exit;
    }

    // 🔒 Bloquear inscripción (concurrencia)
    $stmt = $conn->prepare("
        SELECT i.id
        FROM inscripciones i
        INNER JOIN horarios h ON i.horario_id = h.id
        INNER JOIN talleres t ON h.taller_id = t.id
        WHERE i.alumno_id = ? 
        AND i.horario_id = ?
        AND t.ciclo_id = ?
        FOR UPDATE
    ");
    $stmt->bind_param("iii", $alumno_id, $horario_id, $ciclo['id']);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        $conn->rollback();
        echo json_encode(["ok" => false, "mensaje" => "No estas inscrito en este horario"]);
        exit;
    }

    // 🧾 Eliminar inscripción
    $stmt = $conn->prepare("
        DELETE FROM inscripciones 
        WHERE alumno_id = ? AND horario_id = ?
    ");
    $stmt->bind_param("ii", $alumno_id, $horario_id);

    if (!$stmt->execute()) {
        $conn->rollback();
        echo json_encode(["ok" => false, "mensaje" => "Error al cancelar"]);
        exit;
    }

    $conn->commit();

    echo json_encode([
        "ok" => true,
        "mensaje" => "Inscripcion cancelada correctamente"
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error en servidor"
    ]);
}
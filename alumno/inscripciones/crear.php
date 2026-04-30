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
include("../../helpers/validaciones.php");

$data = json_decode(file_get_contents("php://input"), true);

$alumno_id  = isset($data["alumno_id"]) ? intval($data["alumno_id"]) : 0;
$horario_id = isset($data["horario_id"]) ? intval($data["horario_id"]) : 0;

if ($alumno_id <= 0 || $horario_id <= 0) {
    echo json_encode(["ok" => false, "mensaje" => "Datos invalidos"]);
    exit;
}


$conn->query("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
$conn->query("SET innodb_lock_wait_timeout = 5");

$conn->begin_transaction();


try {

    // 🔒 Iniciar transacción
    $conn->begin_transaction();

    $stmt = $conn->prepare("
    SELECT h.id, h.cupo_maximo, COUNT(i.id) as inscritos
    FROM horarios h
    LEFT JOIN inscripciones i ON h.id = i.horario_id
    WHERE h.id = ?
    GROUP BY h.id
    FOR UPDATE
");
    $stmt->bind_param("i", $horario_id);
    $stmt->execute();

    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        $conn->rollback();
        echo json_encode(["ok" => false, "mensaje" => "Horario no existe"]);
        exit;
    }

    $row = $res->fetch_assoc();
    $cupo_total = $row['cupo_maximo'] + 5;

    if ($row['inscritos'] >= $cupo_total) {
        $conn->rollback();
        echo json_encode(["ok" => false, "mensaje" => "Cupo lleno"]);
        exit;
    }

    // 🧾 Insertar inscripción
    $stmt = $conn->prepare("
        INSERT INTO inscripciones (alumno_id, horario_id)
        VALUES (?, ?)
    ");
    $stmt->bind_param("ii", $alumno_id, $horario_id);

    if ($stmt->errno === 1062) {
    $conn->rollback();
    echo json_encode(["ok" => false, "mensaje" => "Ya inscrito"]);
    exit;
}

    // ✅ Confirmar
    $conn->commit();

    echo json_encode([
        "ok" => true,
        "mensaje" => "Inscripcion realizada correctamente"
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "ok" => false,
        "mensaje" => "Error en servidor"
    ]);
}

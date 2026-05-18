<?php


header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");

header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");

include("../../config/database.php");
include("../../middleware/auth.php");

$user = requireRole(['admin']);

$data = json_decode(file_get_contents("php://input"), true);

$taller_id = isset($data["taller_id"]) ? intval($data["taller_id"]) : 0;

if ($taller_id <= 0) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "ID inválido"
    ]);
    exit;
}

try {

    $conn->begin_transaction();

    // 🧹 1. Eliminar inscripciones relacionadas
    $stmt = $conn->prepare("
        DELETE i FROM inscripciones i
        INNER JOIN horarios h ON i.horario_id = h.id
        WHERE h.taller_id = ?
    ");
    $stmt->bind_param("i", $taller_id);
    $stmt->execute();

    // 🧹 2. Eliminar horarios del taller
    $stmt = $conn->prepare("
        DELETE FROM horarios WHERE taller_id = ?
    ");
    $stmt->bind_param("i", $taller_id);
    $stmt->execute();

    // 🧹 3. Eliminar el taller
    $stmt = $conn->prepare("
        DELETE FROM talleres WHERE id = ?
    ");
    $stmt->bind_param("i", $taller_id);
    $stmt->execute();

    $conn->commit();

    echo json_encode([
        "ok" => true,
        "mensaje" => "Taller eliminado correctamente"
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "ok" => false,
        "mensaje" => "Error al eliminar taller",
        "error" => $e->getMessage()
    ]);
}
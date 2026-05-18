<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");


header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// 🔥 MUY IMPORTANTE (para preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");




include "../../config/database.php";



$data = json_decode(file_get_contents("php://input"), true);

$conn->begin_transaction();

try {

    $stmt = $conn->prepare("
        DELETE FROM inscripciones
        WHERE alumno_id = ? AND horario_id = ?
    ");

    $stmt->bind_param("ii", $data['alumno_id'], $data['horario_id']);

    if (!$stmt->execute()) {
        throw new Exception("Error");
    }

    $conn->commit();

    echo json_encode([
        "ok" => true,
        "mensaje" => "Inscripción cancelada"
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "ok" => false,
        "mensaje" => "Error en servidor"
    ]);
}
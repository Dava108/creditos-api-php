<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include("../../config/database.php");
include("../../middleware/auth.php");

$user = requireRole(['admin']);

$data = json_decode(file_get_contents("php://input"), true);

$inscripcion_id = isset($data["inscripcion_id"])
    ? intval($data["inscripcion_id"])
    : 0;

if ($inscripcion_id <= 0) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "ID inválido"
    ]);

    exit;
}


// iniciar transacción
$conn->begin_transaction();

try {

    // verificar inscripción

    $stmt = $conn->prepare("
        SELECT id, estado
        FROM inscripciones
        WHERE id = ?
    ");

    $stmt->bind_param("i", $inscripcion_id);
    $stmt->execute();

    $res = $stmt->get_result();

    if ($res->num_rows === 0) {

        throw new Exception("Inscripción no encontrada");
    }

    $inscripcion = $res->fetch_assoc();

    if ($inscripcion['estado'] !== 'activa') {

        throw new Exception("La inscripción ya no está activa");
    }


    // acreditar inscripción

    $stmt = $conn->prepare("
        UPDATE inscripciones
        SET
            estado='acreditada',
            fecha_acreditacion=NOW()
        WHERE id=?
    ");

    $stmt->bind_param("i", $inscripcion_id);

    if (!$stmt->execute()) {

        throw new Exception($stmt->error);
    }


    // obtener información para kardex

    $stmt = $conn->prepare("

       SELECT
    i.alumno_id,
    t.nombre AS actividad,
    al.periodo_id,
    t.creditos

FROM inscripciones i

INNER JOIN alumnos al
ON i.alumno_id=al.id

INNER JOIN horarios h
ON i.horario_id=h.id

INNER JOIN talleres t
ON h.taller_id=t.id

WHERE i.id=?
");

    $stmt->bind_param("i", $inscripcion_id);

    $stmt->execute();

    $res = $stmt->get_result();

    $info = $res->fetch_assoc();



    if (!$info) {

        throw new Exception("No se encontró información del taller");
    }


    // evitar duplicados en kardex

    $stmt = $conn->prepare("
        SELECT id
        FROM registro_actividades
        WHERE alumno_id=?
        AND actividad_extra=?
        AND periodo_id=?
    ");

    $stmt->bind_param(
        "isi",
        $info["alumno_id"],
        $info["actividad"],
        $info["periodo_id"]
    );


    $stmt->execute();

    $duplicado = $stmt->get_result();

    if ($duplicado->num_rows == 0) {

        $stmt = $conn->prepare("
        INSERT INTO registro_actividades
        (
        alumno_id,
        actividad_extra,
        periodo_id,
        creditos
        )
        VALUES(?,?,?,?)
        ");

        $stmt->bind_param(
            "isii",
            $info["alumno_id"],
            $info["actividad"],
            $info["periodo_id"],
            $info["creditos"]
        );

        if (!$stmt->execute()) {

            throw new Exception($stmt->error);
        }
    }


    // confirmar cambios

    $conn->commit();

    echo json_encode([
        "ok" => true,
        "mensaje" => "Alumno acreditado correctamente"
    ]);
} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "ok" => false,
        "mensaje" => $e->getMessage()
    ]);
}

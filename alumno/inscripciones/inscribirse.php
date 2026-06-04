<?php

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");

header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");

include("../../config/database.php");
include("../../helpers/validaciones.php");
include("../../middleware/auth.php");

$user = requireRole(['alumno']);

$data = json_decode(
    file_get_contents("php://input"),
    true
);

// DATOS

$alumno_id = $user['id'];

$horario_id = isset($data["horario_id"])
    ? intval($data["horario_id"])
    : 0;

$periodo_id = isset($data["periodo_id"])
    ? intval($data["periodo_id"])
    : 0;

// VALIDACIÓN BÁSICA

if (
    $alumno_id <= 0 ||
    $horario_id <= 0 ||
    $periodo_id <= 0
) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "Datos inválidos"
    ]);

    exit;
}

// VALIDAR QUE EL PERIODO EXISTA

$stmtPeriodo = $conn->prepare("
    SELECT id
    FROM periodos
    WHERE id = ?
");

$stmtPeriodo->bind_param(
    "i",
    $periodo_id
);

$stmtPeriodo->execute();

$resPeriodo = $stmtPeriodo->get_result();

if ($resPeriodo->num_rows === 0) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "Semestre inválido"
    ]);

    exit;
}

// VALIDACIONES DE NEGOCIO

$validacion = validarInscripcion(
    $alumno_id,
    $horario_id,
    $conn
);

if (!$validacion["ok"]) {

    echo json_encode($validacion);
    exit;
}

// OBTENER DATOS HISTÓRICOS DEL HORARIO

$stmtInfo = $conn->prepare("
    SELECT 

        t.nombre,
        t.tipo,
        t.promotor,
        t.ciclo_id,

        h.dia_semana,
        h.hora_inicio,
        h.hora_fin

    FROM horarios h

    INNER JOIN talleres t 
        ON h.taller_id = t.id

    WHERE h.id = ?
");

$stmtInfo->bind_param(
    "i",
    $horario_id
);

$stmtInfo->execute();

$resInfo = $stmtInfo->get_result();

if ($resInfo->num_rows === 0) {

    echo json_encode([
        "ok" => false,
        "mensaje" => "Horario no encontrado"
    ]);

    exit;
}

$info = $resInfo->fetch_assoc();

$dias = [
    1 => "Lunes",
    2 => "Martes",
    3 => "Miércoles",
    4 => "Jueves",
    5 => "Viernes"
];

$horario_snapshot =
    $dias[$info['dia_semana']] . " " .
    substr($info['hora_inicio'], 0, 5) .
    " - " .
    substr($info['hora_fin'], 0, 5);

// INSERT HISTÓRICO

$stmt = $conn->prepare("
    INSERT INTO inscripciones (

        alumno_id,
        horario_id,
        ciclo_id,
        periodo_id,

        estado,

        taller_nombre_snapshot,
        taller_tipo_snapshot,
        horario_snapshot,
        promotor_snapshot,

        fecha_inscripcion

    )

    VALUES (

        ?, ?, ?, ?,

        'activa',

        ?, ?, ?, ?,

        NOW()
    )
");

$stmt->bind_param(
    "iiiissss",

    $alumno_id,
    $horario_id,
    $info['ciclo_id'],
    $periodo_id,

    $info['nombre'],
    $info['tipo'],
    $horario_snapshot,
    $info['promotor']
);

if ($stmt->execute()) {

    echo json_encode([
        "ok" => true,
        "mensaje" => "Inscripción realizada correctamente"
    ]);

} else {

    echo json_encode([
        "ok" => false,
        "mensaje" => "Error SQL",
        "error" => $stmt->error
    ]);

}
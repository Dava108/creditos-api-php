<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include("../../config/database.php");
include("../../middleware/auth.php");

$user = requireRole(['alumno']);

$alumno_id = $user['id'];

$sql = "
SELECT
    i.id,
    t.nombre AS taller,
    t.tipo,
    c.nombre AS ciclo,

    CASE h.dia_semana
        WHEN 1 THEN 'Lunes'
        WHEN 2 THEN 'Martes'
        WHEN 3 THEN 'Miércoles'
        WHEN 4 THEN 'Jueves'
        WHEN 5 THEN 'Viernes'
        WHEN 6 THEN 'Sábado'
        WHEN 7 THEN 'Domingo'
    END AS dia,

    h.hora_inicio,
    h.hora_fin,

    i.estado,
    i.created_at,
    i.fecha_acreditacion

FROM inscripciones i

INNER JOIN horarios h
ON i.horario_id = h.id

INNER JOIN talleres t
ON h.taller_id = t.id

INNER JOIN ciclos c
ON t.ciclo_id = c.id

WHERE i.alumno_id = ?

ORDER BY i.created_at DESC
";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $alumno_id);

$stmt->execute();

$res = $stmt->get_result();

$data=[];

while($row=$res->fetch_assoc()){

    $row['horario']=
        $row['dia'] .
        " ".
        substr($row['hora_inicio'],0,5).
        " - ".
        substr($row['hora_fin'],0,5);

    unset($row['dia']);
    unset($row['hora_inicio']);
    unset($row['hora_fin']);

    $data[]=$row;
}

echo json_encode($data);
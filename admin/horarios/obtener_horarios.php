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

$taller_id = $_GET['taller_id'];

$sql = "
SELECT 
    h.id,
    t.nombre AS taller_nombre,
    t.tipo,
    h.dia_semana,
    CASE h.dia_semana
        WHEN 1 THEN 'Lunes'
        WHEN 2 THEN 'Martes'
        WHEN 3 THEN 'Miércoles'
        WHEN 4 THEN 'Jueves'
        WHEN 5 THEN 'Viernes'
        WHEN 6 THEN 'Sábado'
        WHEN 7 THEN 'Domingo'
    END AS dia_nombre,
    h.hora_inicio,
    h.hora_fin,
    h.espacio,
    h.cupo_maximo,
    h.estado,

    COUNT(i.id) AS inscritos

FROM horarios h

INNER JOIN talleres t 
ON h.taller_id = t.id

LEFT JOIN inscripciones i 
ON i.horario_id = h.id
AND i.estado = 'activa'

WHERE h.taller_id = ?

GROUP BY h.id

ORDER BY h.dia_semana, h.hora_inicio
";


$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $taller_id);
$stmt->execute();

$res = $stmt->get_result();

$data = [];

while ($row = $res->fetch_assoc()) {
    $row['cupo_total'] = $row['cupo_maximo'] + 5;
    $data[] = $row;
}

echo json_encode($data);
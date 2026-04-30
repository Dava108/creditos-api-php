<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");

include("../../config/database.php");

// 1. ciclo activo
$ciclo = $conn->query("SELECT id FROM ciclos WHERE estado='activo' LIMIT 1");

if ($ciclo->num_rows === 0) {
    echo json_encode([]);
    exit;
}

$ciclo_id = $ciclo->fetch_assoc()['id'];

// 2. traer talleres + horarios + inscritos
$sql = "
SELECT 
    t.id as taller_id,
    t.nombre,
    t.tipo,
    t.promotor,
    h.id as horario_id,
    h.dia_semana,
    h.hora_inicio,
    h.hora_fin,
    h.cupo_maximo,
    h.estado,
    COUNT(i.id) as inscritos
FROM talleres t
INNER JOIN horarios h ON t.id = h.taller_id
LEFT JOIN inscripciones i ON h.id = i.horario_id
WHERE t.estado = 'activo'
AND h.estado = 'activo'
AND t.ciclo_id = ?
GROUP BY h.id
ORDER BY t.nombre, h.dia_semana, h.hora_inicio
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ciclo_id);
$stmt->execute();
$res = $stmt->get_result();

$data = [];

while ($row = $res->fetch_assoc()) {

    $cupo_total = $row['cupo_maximo'] + 5;

    if ($row['inscritos'] >= $cupo_total) {
        $estado_cupo = "lleno";
    } else if ($row['inscritos'] >= $row['cupo_maximo']) {
        $estado_cupo = "sobrecupo";
    } else {
        $estado_cupo = "disponible";
    }

    $data[] = [
        "taller_id" => $row['taller_id'],
        "nombre" => $row['nombre'],
        "tipo" => $row['tipo'],
        "promotor" => $row['promotor'],
        "horario_id" => $row['horario_id'],
        "dia_semana" => $row['dia_semana'],
        "hora_inicio" => $row['hora_inicio'],
        "hora_fin" => $row['hora_fin'],
        "inscritos" => intval($row['inscritos']),
        "cupo" => intval($row['cupo_maximo']),
        "estado_cupo" => $estado_cupo
    ];
}

echo json_encode($data);
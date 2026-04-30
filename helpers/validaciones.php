<?php

function validarInscripcion($alumno_id, $horario_id, $conn) {

    // 1. CICLO ACTIVO Y FECHAS
    $sql = "SELECT id, fecha_inicio, fecha_fin 
            FROM ciclos 
            WHERE estado = 'activo' 
            LIMIT 1";
    $res = $conn->query($sql);

    if ($res->num_rows === 0) {
        return ["ok" => false, "mensaje" => "No hay ciclo activo"];
    }

    $ciclo = $res->fetch_assoc();
    $hoy = date('Y-m-d');

    if ($hoy < $ciclo['fecha_inicio'] || $hoy > $ciclo['fecha_fin']) {
        return ["ok" => false, "mensaje" => "Fuera del periodo de inscripcion"];
    }

    // 2. DUPLICADO
    $stmt = $conn->prepare("
        SELECT id FROM inscripciones 
        WHERE alumno_id = ? AND horario_id = ?
    ");
    $stmt->bind_param("ii", $alumno_id, $horario_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        return ["ok" => false, "mensaje" => "Ya estas inscrito en este horario"];
    }

    // 3. OBTENER DATOS DEL HORARIO + TALLER
    $stmt = $conn->prepare("
        SELECT h.dia_semana, h.hora_inicio, h.hora_fin, h.cupo_maximo,
               t.tipo, t.ciclo_id
        FROM horarios h
        INNER JOIN talleres t ON h.taller_id = t.id
        WHERE h.id = ? AND h.estado = 'activo' AND t.estado = 'activo'
    ");
    $stmt->bind_param("i", $horario_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        return ["ok" => false, "mensaje" => "Horario no disponible"];
    }

    $horario = $res->fetch_assoc();

    // validar que pertenece al ciclo activo
    if ($horario['ciclo_id'] != $ciclo['id']) {
        return ["ok" => false, "mensaje" => "Horario fuera del ciclo activo"];
    }

    // 4. LIMITE POR TIPO (deportivo/cultural)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total
        FROM inscripciones i
        INNER JOIN horarios h ON i.horario_id = h.id
        INNER JOIN talleres t ON h.taller_id = t.id
        WHERE i.alumno_id = ?
        AND t.tipo = ?
        AND t.ciclo_id = ?
    ");
    $stmt->bind_param("isi", $alumno_id, $horario['tipo'], $ciclo['id']);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if ($res['total'] >= 1) {
        return ["ok" => false, "mensaje" => "Ya tienes un taller de este tipo"];
    }

    // 5. CHOQUE DE HORARIO
    $stmt = $conn->prepare("
        SELECT h.dia_semana, h.hora_inicio, h.hora_fin
        FROM inscripciones i
        INNER JOIN horarios h ON i.horario_id = h.id
        INNER JOIN talleres t ON h.taller_id = t.id
        WHERE i.alumno_id = ?
        AND t.ciclo_id = ?
    ");
    $stmt->bind_param("ii", $alumno_id, $ciclo['id']);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {

        if ($row['dia_semana'] == $horario['dia_semana']) {

            if (
                $horario['hora_inicio'] < $row['hora_fin'] &&
                $horario['hora_fin'] > $row['hora_inicio']
            ) {
                return ["ok" => false, "mensaje" => "Se empalma con otro horario"];
            }
        }
    }

    // 6. CUPO (con sobrecupo +5)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM inscripciones 
        WHERE horario_id = ?
    ");
    $stmt->bind_param("i", $horario_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    $cupo_total = $horario['cupo_maximo'] + 5;

    if ($res['total'] >= $cupo_total) {
        return ["ok" => false, "mensaje" => "Cupo lleno"];
    }

    // TODO OK
    return ["ok" => true];
}
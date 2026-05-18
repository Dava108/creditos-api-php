<?php

function validarHorario($taller_id, $dia_semana, $hora_inicio, $hora_fin, $conn, $horario_id = null) {

    // 🔴 1. Validación básica
    if ($hora_inicio >= $hora_fin) {
        return ["ok" => false, "mensaje" => "Hora inicio debe ser menor a hora fin"];
    }

    // 🔍 2. Obtener horarios existentes del mismo taller
    $sql = "
        SELECT id, dia_semana, hora_inicio, hora_fin
        FROM horarios
        WHERE taller_id = ?
        AND estado = 'activo'
    ";

    // 🔥 Si es edición, ignorar el mismo registro
    if ($horario_id !== null) {
        $sql .= " AND id != ?";
    }

    $stmt = $conn->prepare($sql);

    if ($horario_id !== null) {
        $stmt->bind_param("ii", $taller_id, $horario_id);
    } else {
        $stmt->bind_param("i", $taller_id);
    }

    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {

        // 🔁 Solo comparar mismo día
        if ($row['dia_semana'] == $dia_semana) {

            // 🔴 3. Validar duplicado exacto
            if (
                $row['hora_inicio'] == $hora_inicio &&
                $row['hora_fin'] == $hora_fin
            ) {
                return ["ok" => false, "mensaje" => "Horario duplicado"];
            }

            // 🔴 4. Validar empalme
            if (
                $hora_inicio < $row['hora_fin'] &&
                $hora_fin > $row['hora_inicio']
            ) {
                return ["ok" => false, "mensaje" => "Horario se empalma con otro"];
            }
        }
    }

    // ✅ Todo correcto
    return ["ok" => true];
}
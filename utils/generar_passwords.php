<?php
include("../config/database.php");

$result = $conn->query("SELECT id, numero_control FROM alumnos");

while ($row = $result->fetch_assoc()) {

    $numero = $row["numero_control"];
    $hash = password_hash($numero, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE alumnos SET password=? WHERE id=?");
    $stmt->bind_param("si", $hash, $row["id"]);
    $stmt->execute();
}

echo "Passwords generados correctamente";
<?php
include("../config/database.php");

$nombre = "David Armando Valois Martinez";
$numero = "2019123074";
$rol = "admin";

// password = numero_control
$password = password_hash($numero, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO alumnos (nombre, numero_control, rol, password) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $nombre, $numero, $rol, $password);

$stmt->execute();

echo "Admin creado correctamente";
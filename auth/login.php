<?php

session_start();

error_reporting(E_ALL);

ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");


header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

include("../config/database.php");

$data = json_decode(file_get_contents("php://input"), true);

$numero_control = $data["numero_control"];
$password = $data["password"];

$query = "SELECT * FROM alumnos WHERE numero_control = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $numero_control);
$stmt->execute();

$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {

    if (password_verify($password, $user["password"])) {

        $_SESSION['user'] = [
            "id" => $user["id"],
            "nombre" => $user["nombre"],
            "numero_control" => $user["numero_control"],
            "rol" => $user["rol"]
        ];

        echo json_encode([
            "status" => "ok",
            "user" => [
                "id" => $user["id"],
                "nombre" => $user["nombre"],
                "numero_control" => $user["numero_control"],
                "rol" => $user["rol"]
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Contraseña incorrecta"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Usuario no encontrado"]);
}

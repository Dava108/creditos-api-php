<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json");

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");

function requireRole($roles = []) {

    //  verificar sesión
    if (!isset($_SESSION['user'])) {

        http_response_code(401);

        echo json_encode([
            "ok" => false,
            "mensaje" => "No autenticado"
        ]);

        exit;
    }

    $user = $_SESSION['user'];

    //  verificar rol
    if (!in_array($user['rol'], $roles)) {

        http_response_code(403);

        echo json_encode([
            "ok" => false,
            "mensaje" => "Sin permisos"
        ]);

        exit;
    }

    //  devolver usuario
    return $user;
}
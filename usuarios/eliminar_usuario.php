<?php
header("Access-Control-Allow-Origin: *");

include("../config/database.php");

$id = $_GET["id"];

$conn->query("DELETE FROM alumnos WHERE id=$id");

echo json_encode(["status" => "ok"]);
<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/BiometricMapping.php';

$db = (new Database())->getConnection();
$mapping = new BiometricMapping($db);

$mapping->user_id_global = $_POST['user_id_global'] ?? null;
$mapping->id_aula = $_POST['id_aula'] ?? null;
$mapping->sensor_slot = $_POST['sensor_slot'] ?? null;

if (!$mapping->user_id_global || !$mapping->id_aula || !$mapping->sensor_slot) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Faltan datos obligatorios"
    ]);
    exit();
}

if ($mapping->crear()) {
    echo json_encode([
        "status" => "success",
        "message" => "Mapping creado correctamente"
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error al crear mapping"
    ]);
}
?>
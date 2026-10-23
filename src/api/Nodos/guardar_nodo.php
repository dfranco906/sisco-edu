<?php
// src/api/Nodos/guardar_nodo.php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/NodoEsp32.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rolUsuario = strtolower($_SESSION['rol'] ?? '');
if (!in_array($rolUsuario, ['superadmin', 'administracion', 'administrador'], true)) {
    http_response_code(403);
    echo json_encode([
        "status" => "error",
        "message" => "Acceso restringido. Se requieren privilegios administrativos."
    ]);
    exit();
}

$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true);
$input = is_array($jsonData) ? $jsonData : $_POST;

$id_nodo = !empty($input['id_nodo']) ? (int)$input['id_nodo'] : null;
$id_aula = !empty($input['id_aula']) ? (int)$input['id_aula'] : null;
$lora_id = !empty($input['lora_id']) ? (int)$input['lora_id'] : null;
$lora_red_id = !empty($input['lora_red_id']) ? (int)$input['lora_red_id'] : 18;
$room_id = trim((string)($input['room_id'] ?? ''));

if (!$id_aula) {
    http_response_code(422);
    echo json_encode([
        "status" => "error",
        "message" => "Debe seleccionar un aula válida para asignar el nodo."
    ]);
    exit();
}

if (!$lora_id || $lora_id < 1 || $lora_id > 65535) {
    http_response_code(422);
    echo json_encode([
        "status" => "error",
        "message" => "El ID LoRa debe ser un número entero entre 1 y 65535."
    ]);
    exit();
}

try {
    $db = (new Database())->getConnection();
    $nodoModel = new NodoEsp32($db);

    $resultado = $nodoModel->guardar([
        'id_nodo' => $id_nodo,
        'id_aula' => $id_aula,
        'lora_id' => $lora_id,
        'lora_red_id' => $lora_red_id,
        'room_id' => $room_id,
        'tipo' => 'AULA'
    ]);

    if ($resultado['success']) {
        echo json_encode([
            "status" => "success",
            "message" => $resultado['message'],
            "data" => [
                "id_nodo" => $resultado['id']
            ]
        ]);
    } else {
        http_response_code(422);
        echo json_encode([
            "status" => "error",
            "message" => $resultado['message']
        ]);
    }
} catch (Throwable $e) {
    error_log("Error en guardar_nodo.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al procesar el guardado del nodo."
    ]);
}

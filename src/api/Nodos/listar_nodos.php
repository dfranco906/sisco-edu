<?php
// src/api/Nodos/listar_nodos.php
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

try {
    $db = (new Database())->getConnection();
    $nodoModel = new NodoEsp32($db);

    $nodos = $nodoModel->listarTodos();
    $aulasDisponibles = $nodoModel->obtenerAulasDisponibles();
    $siguienteLoraId = $nodoModel->obtenerSiguienteLoraId(18);

    echo json_encode([
        "status" => "success",
        "data" => [
            "nodos" => $nodos,
            "aulas_disponibles" => $aulasDisponibles,
            "siguiente_lora_id" => $siguienteLoraId,
            "total" => count($nodos)
        ]
    ]);
} catch (Throwable $e) {
    error_log("Error en listar_nodos.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error al obtener la lista de nodos LoRa."
    ]);
}

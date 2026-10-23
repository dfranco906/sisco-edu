<?php
// src/api/Nodos/eliminar_nodo.php
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

$id_nodo = !empty($input['id_nodo']) ? (int)$input['id_nodo'] : (!empty($_GET['id_nodo']) ? (int)$_GET['id_nodo'] : null);

if (!$id_nodo) {
    http_response_code(422);
    echo json_encode([
        "status" => "error",
        "message" => "ID de nodo no especificado."
    ]);
    exit();
}

try {
    $db = (new Database())->getConnection();
    $nodoModel = new NodoEsp32($db);

    if ($nodoModel->desactivar($id_nodo)) {
        echo json_encode([
            "status" => "success",
            "message" => "Nodo LoRa desvinculado exitosamente."
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "No se pudo desvincular el nodo."
        ]);
    }
} catch (Throwable $e) {
    error_log("Error en eliminar_nodo.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al desvincular el nodo."
    ]);
}

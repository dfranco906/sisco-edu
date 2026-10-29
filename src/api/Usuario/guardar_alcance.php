<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../classes/AlcanceUsuario.php';

// Exclusivo para SuperAdmin
verificarRol(['SuperAdmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
    exit;
}

$idUsuario = 0;
$idsGrados = [];

// Intentar leer JSON body
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $data = json_decode($rawInput, true);
    if (is_array($data)) {
        $idUsuario = (int)($data['id_usuario'] ?? 0);
        if (isset($data['ids_grados']) && is_array($data['ids_grados'])) {
            $idsGrados = $data['ids_grados'];
        }
    }
}

// Fallback a $_POST convencional
if ($idUsuario <= 0 && isset($_POST['id_usuario'])) {
    $idUsuario = (int)$_POST['id_usuario'];
    if (isset($_POST['ids_grados'])) {
        if (is_array($_POST['ids_grados'])) {
            $idsGrados = $_POST['ids_grados'];
        } elseif (is_string($_POST['ids_grados'])) {
            $decoded = json_decode($_POST['ids_grados'], true);
            $idsGrados = is_array($decoded) ? $decoded : explode(',', $_POST['ids_grados']);
        }
    }
}

if ($idUsuario <= 0) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "El campo id_usuario es obligatorio y debe ser válido"
    ]);
    exit;
}

try {
    $db = (new Database())->getConnection();

    // Sanitizar array de grados
    $idsLimpios = [];
    foreach ($idsGrados as $id) {
        $idNum = (int)$id;
        if ($idNum > 0) {
            $idsLimpios[] = $idNum;
        }
    }
    $idsLimpios = array_values(array_unique($idsLimpios));

    $resultado = AlcanceUsuario::guardarAlcance($db, $idUsuario, $idsLimpios);

    echo json_encode([
        "status" => "success",
        "message" => "Alcance operativo guardado correctamente",
        "data" => $resultado
    ]);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
} catch (Throwable $e) {
    error_log("[Usuario::guardar_alcance] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al guardar el alcance del usuario"
    ]);
}

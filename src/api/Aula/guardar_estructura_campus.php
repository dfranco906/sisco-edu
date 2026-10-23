<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Aula.php';

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
    $input = file_get_contents('php://input');
    $datos = json_decode($input, true);

    if (!is_array($datos)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Formato de datos inválido. Se esperaba JSON."
        ]);
        exit();
    }

    $bloques = $datos['bloques'] ?? [];
    $pisos = $datos['pisos'] ?? [];

    if (!is_array($bloques) || !is_array($pisos)) {
        http_response_code(422);
        echo json_encode([
            "status" => "error",
            "message" => "La estructura de bloques y pisos debe ser un arreglo válido."
        ]);
        exit();
    }

    $db = (new Database())->getConnection();
    $aula = new Aula($db);

    $exito = $aula->guardarEstructuraCampus($bloques, $pisos);

    if (!$exito) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "No se pudo guardar la configuración edilicia."
        ]);
        exit();
    }

    echo json_encode([
        "status" => "success",
        "message" => "Estructura institucional (bloques y pisos) actualizada correctamente.",
        "data" => [
            "bloques" => $bloques,
            "pisos" => $pisos
        ]
    ]);
} catch (Throwable $e) {
    error_log("Error al guardar estructura campus: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al guardar la estructura edilicia."
    ]);
}

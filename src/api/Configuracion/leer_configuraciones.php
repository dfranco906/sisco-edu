<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/ConfiguracionSistema.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Seguridad RBAC: SuperAdmin o Administración pueden ver la configuración
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
    $configModel = new ConfiguracionSistema($db);
    $configModel->asegurarTabla();

    $datos = $configModel->obtenerTodas();
    $mapa = $configModel->obtenerMapa();

    echo json_encode([
        "status" => "success",
        "data" => [
            "listado" => $datos,
            "valores" => $mapa
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error al obtener las configuraciones del sistema",
        "debug" => $e->getMessage()
    ]);
}

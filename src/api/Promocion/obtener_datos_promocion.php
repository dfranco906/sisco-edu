<?php
// src/api/Promocion/obtener_datos_promocion.php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../classes/Promocion.php';

// RBAC: Solo SuperAdmin, Administrador y Administracion
verificarRol(['SuperAdmin', 'Administrador', 'Administracion']);

try {
    $db = (new Database())->getConnection();
    $promocion = new Promocion($db);

    $grados = $promocion->obtenerCatalogoGrados();

    $rawGrado = $_GET['id_grado'] ?? null;
    $idGrado = filter_var($rawGrado, FILTER_VALIDATE_INT) ?: null;

    $estudiantes = [];
    if ($idGrado && $idGrado > 0) {
        $estudiantes = $promocion->obtenerEstudiantesPorGrado($idGrado);
    }

    echo json_encode([
        'status' => 'success',
        'data'   => [
            'grados'      => $grados,
            'id_grado'    => $idGrado,
            'estudiantes' => $estudiantes,
            'total'       => count($estudiantes)
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log("Error en obtener_datos_promocion.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Error al cargar datos de promoción curricular.',
        'debug'   => (defined('MODO_ENTORNO') && MODO_ENTORNO === 'local') ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE);
}

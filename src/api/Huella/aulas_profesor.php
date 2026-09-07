<?php
require_once __DIR__ . '/../../config/api_auth.php';
require_once __DIR__ . '/../../classes/DistribucionHuellaProfesor.php';

requerirMetodo(['GET']);
usuarioActual(['SuperAdmin', 'Administracion', 'Coordinador']);

try {
    $userIdGlobal = trim((string) ($_GET['user_id_global'] ?? ''));
    if ($userIdGlobal === '' || strlen($userIdGlobal) > 50) {
        responderJson(['success' => false, 'status' => 'error', 'message' => 'Profesor no válido.'], 422);
    }
    $db = (new Database())->getConnection();
    $servicio = new DistribucionHuellaProfesor($db);
    $profesor = $servicio->obtenerProfesorActivo($userIdGlobal);
    if (!$profesor) responderJson(['success' => false, 'status' => 'error', 'message' => 'Profesor no encontrado.'], 404);
    $huella = $servicio->ultimaHuellaActiva((int) $profesor['id_profesor']);
    $aulas = $servicio->aulasAsignadas((int) $profesor['id_profesor'], $huella ? (int) $huella['id_huella'] : null);
    responderJson([
        'success' => true,
        'status' => 'success',
        'data' => [
            'profesor' => $profesor,
            'huella' => $huella,
            'aulas' => $aulas
        ]
    ]);
} catch (Throwable $e) {
    error_log('aulas_profesor_huella: ' . $e->getMessage());
    responderJson(['success' => false, 'status' => 'error', 'message' => 'No se pudieron consultar las aulas del profesor.'], 500);
}

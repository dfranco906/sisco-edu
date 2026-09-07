<?php
require_once __DIR__ . '/../../config/api_auth.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/DistribucionHuellaProfesor.php';

requerirMetodo(['POST']);
usuarioActual(['SuperAdmin', 'Administracion', 'Coordinador']);

try {
    $userIdGlobal = trim((string) ($_POST['user_id_global'] ?? ''));
    $idsAula = $_POST['id_aulas'] ?? [];
    if ($userIdGlobal === '' || strlen($userIdGlobal) > 50) {
        responderJson(['success' => false, 'status' => 'error', 'message' => 'Profesor no válido.'], 422);
    }
    $db = (new Database())->getConnection();
    $servicio = new DistribucionHuellaProfesor($db);
    $profesor = $servicio->obtenerProfesorActivo($userIdGlobal);
    if (!$profesor) responderJson(['success' => false, 'status' => 'error', 'message' => 'Profesor no encontrado.'], 404);
    $huella = $servicio->ultimaHuellaActiva((int) $profesor['id_profesor']);
    if (!$huella) responderJson(['success' => false, 'status' => 'error', 'message' => 'El profesor todavía no tiene una huella registrada.'], 409);

    $db->beginTransaction();
    $resultado = $servicio->encolar((int) $huella['id_huella'], (int) $profesor['id_profesor'], is_array($idsAula) ? $idsAula : [$idsAula]);
    $db->commit();
    $cantidad = count($resultado['creadas']);
    $gatewayAvisado = $cantidad > 0 ? DistribucionHuellaProfesor::avisarGateway() : false;
    $mensaje = $cantidad > 0
        ? "Huella preparada para {$cantidad} aula(s)."
        : 'La huella ya estaba pendiente, enviada o instalada en las aulas seleccionadas.';
    responderJson([
        'success' => true,
        'status' => 'success',
        'message' => $mensaje,
        'data' => $resultado + ['gateway_avisado' => $gatewayAvisado]
    ], $cantidad > 0 ? 201 : 200);
} catch (InvalidArgumentException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    responderJson(['success' => false, 'status' => 'error', 'message' => $e->getMessage()], 422);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('sincronizar_profesor_huella: ' . $e->getMessage());
    responderJson(['success' => false, 'status' => 'error', 'message' => 'No se pudo preparar la sincronización de la huella.'], 500);
}

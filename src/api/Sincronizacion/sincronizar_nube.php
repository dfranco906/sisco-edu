<?php
// src/api/Sincronizacion/sincronizar_nube.php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/SincronizadorNube.php';

try {
    $direccion = $_GET['direccion'] ?? 'subir'; // 'subir' (local -> nube) o 'bajar' (nube -> local)
    $sincronizador = new SincronizadorNube();

    if ($direccion === 'bajar') {
        $resultado = $sincronizador->sincronizarNubeHaciaLocal();
        $mensaje = "Datos descargados desde la nube a la base local exitosamente.";
    } else {
        $resultado = $sincronizador->sincronizarLocalHaciaNube();
        $mensaje = "Datos locales sincronizados hacia Supabase Cloud exitosamente.";
    }

    echo json_encode([
        'status'  => 'success',
        'message' => $mensaje,
        'data'    => $resultado
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    error_log("Error en sincronizar_nube.php: " . $e->getMessage());

    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
        'data'    => null
    ], JSON_UNESCAPED_UNICODE);
}

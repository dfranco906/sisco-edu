<?php
// src/api/Promocion/ejecutar_promocion.php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../classes/Promocion.php';
require_once __DIR__ . '/../../config/biometria.php';

// RBAC: Únicamente Administrador y SuperAdmin pueden ejecutar promociones curriculares masivas
verificarRol(['SuperAdmin', 'Administrador']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Método no permitido. Se requiere POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (!is_array($input)) {
        $input = $_POST;
    }

    $idGradoOrigen  = filter_var($input['id_grado_origen'] ?? null, FILTER_VALIDATE_INT) ?: 0;
    $idGradoDestino = filter_var($input['id_grado_destino'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $idsEstudiantes = $input['ids_estudiantes'] ?? [];
    $migrarHuellas  = !empty($input['migrar_huellas']);
    $esGraduacion   = !empty($input['es_graduacion']);
    $anhoLectivo    = filter_var($input['anho_lectivo'] ?? (int)date('Y') + 1, FILTER_VALIDATE_INT) ?: (int)date('Y') + 1;

    if ($idGradoOrigen <= 0) {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Debe especificar el grado de origen de los estudiantes.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!$esGraduacion && (!$idGradoDestino || $idGradoDestino <= 0)) {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Debe seleccionar el grado de destino o marcar la opción de graduación / egreso.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!$esGraduacion && $idGradoOrigen === $idGradoDestino) {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'El grado de destino no puede ser igual al grado de origen.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!is_array($idsEstudiantes) || empty($idsEstudiantes)) {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Debe seleccionar al menos un estudiante para promover.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $db = (new Database())->getConnection();
    $promocion = new Promocion($db);

    $resultado = $promocion->ejecutar(
        idGradoOrigen: $idGradoOrigen,
        idGradoDestino: $esGraduacion ? null : $idGradoDestino,
        idsEstudiantes: $idsEstudiantes,
        migrarHuellas: $migrarHuellas,
        anhoLectivo: $anhoLectivo,
        esGraduacion: $esGraduacion
    );

    // Si se encolaron huellas para el aula nueva, intentamos notificar proactivamente al Gateway LoRa
    $avisoGateway = null;
    if (!empty($resultado['huellas_migradas']) && $resultado['huellas_migradas'] > 0) {
        try {
            $avisoGateway = avisarGatewaySync();
        } catch (Throwable $eG) {
            error_log("Aviso a Gateway tras promoción: " . $eG->getMessage());
        }
    }

    echo json_encode([
        'status'  => 'success',
        'message' => $resultado['message'],
        'data'    => array_merge($resultado, [
            'notificacion_gateway' => $avisoGateway
        ])
    ], JSON_UNESCAPED_UNICODE);

} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("Error en ejecutar_promocion.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Ocurrió un error interno al ejecutar la promoción curricular.',
        'debug'   => (defined('MODO_ENTORNO') && MODO_ENTORNO === 'local') ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE);
}

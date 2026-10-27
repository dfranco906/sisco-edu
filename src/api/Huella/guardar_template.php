<?php
// src/api/Huella/guardar_template.php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/biometria.php';

$db = (new Database())->getConnection();

$tipo = strtolower(trim($_POST['tipo_persona'] ?? $_POST['tipo_usuario'] ?? 'estudiante'));
$template = trim($_POST['template'] ?? '');
$crcRecibido = strtolower(trim($_POST['crc32'] ?? ''));
$rawIdRecibido = $_POST['id_persona'] ?? $_POST['id_estudiante'] ?? $_POST['id_profesor'] ?? null;
$idRecibido = filter_var($rawIdRecibido, FILTER_VALIDATE_INT) ?: null;
$userIdRecibido = trim($_POST['user_id_global'] ?? '');
$bytesRecibidos = isset($_POST['bytes']) ? filter_var($_POST['bytes'], FILTER_VALIDATE_INT) : null;

// 1. Guard clause: Validación estricta del contrato biométrico (1536 bytes / 3072 HEX)
if (!in_array($tipo, ['estudiante', 'profesor'], true)
    || !es_template_huella_hex_valido($template)
    || ($bytesRecibidos !== null && $bytesRecibidos !== false && $bytesRecibidos !== HUELLA_TEMPLATE_BYTES)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Template incompatible: se requieren 1536 bytes binarios representados en 3072 caracteres HEX']);
    exit;
}

// 2. Guard clause: Validación de integridad CRC32
$templateBinario = hex2bin($template);
$crcCalculado = $templateBinario === false ? '' : strtolower(hash('crc32b', $templateBinario));
if (preg_match('/\A[0-9a-f]{8}\z/D', $crcRecibido) !== 1
    || $crcCalculado === ''
    || !hash_equals($crcCalculado, $crcRecibido)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'CRC32 del template no coincide con el informado por el registrador']);
    exit;
}

try {
    $tabla = $tipo === 'estudiante' ? 'estudiantes' : 'profesores';
    $idCampo = $tipo === 'estudiante' ? 'id_estudiante' : 'id_profesor';

    // 3. Localizar la persona a registrar
    if ($idRecibido) {
        $stmt = $db->prepare("SELECT {$idCampo}, user_id_global, nombre, apellido FROM {$tabla} WHERE {$idCampo} = :id AND activo = 1 LIMIT 1");
        $stmt->execute([':id' => $idRecibido]);
    } elseif ($userIdRecibido !== '') {
        $stmt = $db->prepare("SELECT {$idCampo}, user_id_global, nombre, apellido FROM {$tabla} WHERE user_id_global = :uid AND activo = 1 LIMIT 1");
        $stmt->execute([':uid' => $userIdRecibido]);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Falta identificador de la persona']);
        exit;
    }

    $persona = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$persona || empty($persona['user_id_global'])) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Persona no encontrada o sin identificador institucional (user_id_global)']);
        exit;
    }

    $idPersona = (int)$persona[$idCampo];

    // 4. Momento 1: Transacción atómica de guardado y vinculación biométrica
    $db->beginTransaction();

    // Desactivar plantillas anteriores para mantener única la plantilla activa
    $db->prepare("UPDATE huellas_templates SET activo = 0 WHERE {$idCampo} = :id")
       ->execute([':id' => $idPersona]);

    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $returningHuella = ($driver === 'pgsql') ? ' RETURNING id_huella' : '';

    // Insertar nueva plantilla validada
    $stmtIns = $db->prepare("
        INSERT INTO huellas_templates (
            user_id_global, {$idCampo}, fingerprint_data, crc32_hash, formato, pendiente_sync, activo
        ) VALUES (
            :uid, :id, :data, :crc, 'HEX', 1, 1
        ){$returningHuella}
    ");
    $stmtIns->execute([
        ':uid'  => $persona['user_id_global'],
        ':id'   => $idPersona,
        ':data' => strtolower($template),
        ':crc'  => $crcCalculado
    ]);
    $idHuella = ($driver === 'pgsql') ? (int)$stmtIns->fetchColumn() : (int)$db->lastInsertId();

    // Actualizar tabla principal (estudiantes o profesores)
    if ($tipo === 'estudiante') {
        $db->prepare("
            UPDATE estudiantes 
            SET huella_id = :huella, huella_registrada = 1, pendiente_sync = 1
            WHERE id_estudiante = :id
        ")->execute([
            ':huella' => $idHuella,
            ':id'     => $idPersona
        ]);
    } else {
        $db->prepare("
            UPDATE profesores 
            SET huella_id = :huella, pendiente_sync = 1
            WHERE id_profesor = :id
        ")->execute([
            ':huella' => $idHuella,
            ':id'     => $idPersona
        ]);
    }

    $db->commit();

    http_response_code(200);
    echo json_encode([
        'status'  => 'success',
        'message' => "Huella biométrica de {$persona['nombre']} {$persona['apellido']} registrada y guardada exitosamente.",
        'data'    => [
            'id_huella'       => $idHuella,
            'id_persona'      => $idPersona,
            'user_id_global'  => $persona['user_id_global'],
            'tipo_usuario'    => $tipo,
            'crc32'           => $crcCalculado
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error en Huella/guardar_template: " . $e->getMessage());
    http_response_code(500);

    $modo = defined('MODO_ENTORNO') ? MODO_ENTORNO : 'cloud';
    $mensaje = ($modo === 'local')
        ? 'Error al guardar template biométrico: ' . $e->getMessage()
        : 'Error interno al guardar template biométrico';

    echo json_encode([
        'status'  => 'error',
        'message' => $mensaje
    ], JSON_UNESCAPED_UNICODE);
}

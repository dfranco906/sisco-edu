<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/biometria.php';

$db = (new Database())->getConnection();

$tipo = strtolower(trim($_POST['tipo_persona'] ?? $_POST['tipo_usuario'] ?? 'estudiante'));
$template = trim($_POST['template'] ?? '');
$crcRecibido = strtolower(trim($_POST['crc32'] ?? ''));
$idRecibido = filter_input(INPUT_POST, 'id_persona', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id_estudiante', FILTER_VALIDATE_INT);
$userIdRecibido = trim($_POST['user_id_global'] ?? '');
$bytesRecibidos = filter_input(INPUT_POST, 'bytes', FILTER_VALIDATE_INT);

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

function avisarGatewaySync(): bool {
    if (!function_exists('curl_init') || !defined('GATEWAY_SYNC_URL') || empty(GATEWAY_SYNC_URL)) {
        return false;
    }
    $ch = curl_init(GATEWAY_SYNC_URL);
    if (!$ch) return false;
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['X-GATEWAY-KEY: ' . GATEWAY_API_KEY],
        CURLOPT_TIMEOUT => 3,
        CURLOPT_CONNECTTIMEOUT => 2
    ]);
    $respuesta = curl_exec($ch);
    $codigo = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $respuesta !== false && $codigo >= 200 && $codigo < 300;
}

try {
    $tabla = $tipo === 'estudiante' ? 'estudiantes' : 'profesores';
    $idCampo = $tipo === 'estudiante' ? 'id_estudiante' : 'id_profesor';

    // 3. Localizar la persona a registrar
    if ($idRecibido) {
        $stmt = $db->prepare("SELECT {$idCampo}, user_id_global" . ($tipo === 'estudiante' ? ', id_grado, id_aula' : '') . " FROM {$tabla} WHERE {$idCampo} = :id AND activo = 1 LIMIT 1");
        $stmt->execute([':id' => $idRecibido]);
    } elseif ($userIdRecibido !== '') {
        $stmt = $db->prepare("SELECT {$idCampo}, user_id_global" . ($tipo === 'estudiante' ? ', id_grado, id_aula' : '') . " FROM {$tabla} WHERE user_id_global = :uid AND activo = 1 LIMIT 1");
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

    // 4. Resolver el aula física correspondiente al nodo LoRa destinatario
    $idAula = filter_input(INPUT_POST, 'id_aula', FILTER_VALIDATE_INT);
    if (!$idAula) {
        if ($tipo === 'estudiante') {
            if (!empty($persona['id_grado'])) {
                $stmtAula = $db->prepare("SELECT id_aula FROM grados WHERE id_grado = :grado AND activo = 1 LIMIT 1");
                $stmtAula->execute([':grado' => $persona['id_grado']]);
                $idAula = (int)$stmtAula->fetchColumn();
            }
            if (!$idAula && !empty($persona['id_aula'])) {
                $idAula = (int)$persona['id_aula'];
            }
        } else {
            // Para profesores, asignar primera aula programada en sus horarios
            $stmtAula = $db->prepare("
                SELECT h.id_aula 
                FROM horarios h 
                INNER JOIN asignacion_docente ad ON ad.id_asignacion = h.id_asignacion 
                WHERE ad.id_profesor = :profesor AND h.activo = 1 AND h.id_aula IS NOT NULL 
                ORDER BY h.id_horario ASC LIMIT 1
            ");
            $stmtAula->execute([':profesor' => $idPersona]);
            $idAula = (int)$stmtAula->fetchColumn();
        }
    }

    // Aula predeterminada de laboratorio si no cuenta con asignación física previa
    if (!$idAula) {
        $idAula = 18;
    }

    // 5. Transacción atómica de guardado y encolado de sincronización
    $db->beginTransaction();

    // Desactivar plantillas anteriores para mantener única la plantilla activa
    $db->prepare("UPDATE huellas_templates SET activo = 0 WHERE {$idCampo} = :id")
       ->execute([':id' => $idPersona]);

    // Insertar nueva plantilla validada
    $stmtIns = $db->prepare("
        INSERT INTO huellas_templates (
            user_id_global, {$idCampo}, fingerprint_data, crc32_hash, formato, pendiente_sync, activo
        ) VALUES (
            :uid, :id, :data, :crc, 'HEX', 1, 1
        )
    ");
    $stmtIns->execute([
        ':uid' => $persona['user_id_global'],
        ':id' => $idPersona,
        ':data' => strtolower($template),
        ':crc' => $crcCalculado
    ]);
    $idHuella = (int)$db->lastInsertId();

    // Actualizar tabla principal (estudiantes o profesores)
    if ($tipo === 'estudiante') {
        $db->prepare("
            UPDATE estudiantes 
            SET huella_id = :huella, huella_registrada = 1, pendiente_sync = 1, id_aula = COALESCE(:aula, id_aula)
            WHERE id_estudiante = :id
        ")->execute([
            ':huella' => $idHuella,
            ':aula' => $idAula,
            ':id' => $idPersona
        ]);
    } else {
        $db->prepare("
            UPDATE profesores 
            SET huella_id = :huella, huella_registrada = 1
            WHERE id_profesor = :id
        ")->execute([
            ':huella' => $idHuella,
            ':id' => $idPersona
        ]);
    }

    // Encolar en sync_biometrica para transporte LoRa hacia el nodo del aula
    $db->prepare("
        INSERT INTO sync_biometrica (id_huella, id_aula, estado, intentos) 
        VALUES (:huella, :aula, 'PENDIENTE', 0)
    ")->execute([
        ':huella' => $idHuella,
        ':aula' => $idAula
    ]);
    $idSync = (int)$db->lastInsertId();

    $db->commit();

    // Notificar al Gateway para que inicie la descarga hacia el aula correspondiente
    $gatewayAvisado = avisarGatewaySync();

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Huella registrada correctamente. Encolada para sincronización LoRa al nodo del aula.',
        'data' => [
            'id_huella' => $idHuella,
            'id_sync' => $idSync,
            'id_aula' => $idAula,
            'user_id_global' => $persona['user_id_global'],
            'crc32' => $crcCalculado,
            'gateway_avisado' => $gatewayAvisado
        ]
    ]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error en Huella/guardar_template: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error interno al guardar template biométrico']);
}

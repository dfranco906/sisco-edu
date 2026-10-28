<?php
// src/api/Huella/enviar_huella_aulas.php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/red.php';
require_once __DIR__ . '/../../config/biometria.php';

$tipo = strtolower(trim($_POST['tipo_persona'] ?? $_POST['tipo_usuario'] ?? ''));
$rawId = $_POST['id_persona'] ?? $_POST['id_profesor'] ?? $_POST['id_estudiante'] ?? null;
$idPersona = filter_var($rawId, FILTER_VALIDATE_INT) ?: null;
$userIdGlobal = trim($_POST['user_id_global'] ?? '');
$rawAula = $_POST['id_aula'] ?? null;
$idAulaManual = filter_var($rawAula, FILTER_VALIDATE_INT) ?: null;
$rawHuella = $_POST['id_huella'] ?? null;
$idHuellaManual = filter_var($rawHuella, FILTER_VALIDATE_INT) ?: null;
$forzarReenvio = isset($_POST['forzar']) && ((string)$_POST['forzar'] === '1' || (string)$_POST['forzar'] === 'true');

// Soporte para selección múltiple de aulas (array o cadena separada por comas)
$aulasSeleccionadasIds = [];
if (!empty($_POST['aulas_ids'])) {
    if (is_array($_POST['aulas_ids'])) {
        $aulasSeleccionadasIds = array_filter(array_map('intval', $_POST['aulas_ids']), fn($id) => $id > 0);
    } elseif (is_string($_POST['aulas_ids'])) {
        $aulasSeleccionadasIds = array_filter(array_map('intval', explode(',', $_POST['aulas_ids'])), fn($id) => $id > 0);
    }
}

// 1. Guard clause: Parámetros básicos válidos
if (!in_array($tipo, ['estudiante', 'profesor'], true)) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Tipo de usuario inválido. Debe ser estudiante o profesor.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$idPersona && $userIdGlobal === '') {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Se requiere el ID o identificador global (user_id_global) de la persona.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $tabla = ($tipo === 'estudiante') ? 'estudiantes' : 'profesores';
    $idCampo = ($tipo === 'estudiante') ? 'id_estudiante' : 'id_profesor';

    // 2. Obtener persona
    if ($idPersona) {
        $stmtPersona = $db->prepare("SELECT {$idCampo}, user_id_global, nombre, apellido, huella_id FROM {$tabla} WHERE {$idCampo} = :id AND activo = 1 LIMIT 1");
        $stmtPersona->execute([':id' => $idPersona]);
    } else {
        $stmtPersona = $db->prepare("SELECT {$idCampo}, user_id_global, nombre, apellido, huella_id FROM {$tabla} WHERE user_id_global = :uid AND activo = 1 LIMIT 1");
        $stmtPersona->execute([':uid' => $userIdGlobal]);
    }

    $persona = $stmtPersona->fetch(PDO::FETCH_ASSOC);
    if (!$persona) {
        http_response_code(404);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Persona no encontrada en el sistema o inactiva.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $idPersona = (int)$persona[$idCampo];
    $nombreCompleto = trim("{$persona['nombre']} {$persona['apellido']}");

    // 3. Obtener huella específica o huella activa y validar integridad
    if ($idHuellaManual) {
        $stmtHuella = $db->prepare("
            SELECT id_huella, fingerprint_data, crc32_hash, formato, activo 
            FROM huellas_templates 
            WHERE id_huella = :id_huella AND {$idCampo} = :id AND activo = 1 
            LIMIT 1
        ");
        $stmtHuella->execute([':id_huella' => $idHuellaManual, ':id' => $idPersona]);
    } else {
        $stmtHuella = $db->prepare("
            SELECT id_huella, fingerprint_data, crc32_hash, formato, activo 
            FROM huellas_templates 
            WHERE {$idCampo} = :id AND activo = 1 
            ORDER BY id_huella DESC 
            LIMIT 1
        ");
        $stmtHuella->execute([':id' => $idPersona]);
    }
    $huella = $stmtHuella->fetch(PDO::FETCH_ASSOC);

    if (!$huella || empty($huella['fingerprint_data']) || !es_template_huella_hex_valido(trim((string)$huella['fingerprint_data']))) {
        http_response_code(404);
        echo json_encode([
            'status'  => 'error',
            'message' => "{$nombreCompleto} no cuenta con una huella biométrica válida registrada en el sistema. Registre la huella primero."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $idHuella = (int)$huella['id_huella'];

    // 4. Determinar aulas destino
    $aulasDestino = [];

    if (!empty($aulasSeleccionadasIds)) {
        // Aulas seleccionadas explícitamente desde el modal de selección de aulas
        $inPlaceholders = implode(',', array_fill(0, count($aulasSeleccionadasIds), '?'));
        $stmtSel = $db->prepare("SELECT id_aula, nombre, codigo_sala, codigo FROM aulas WHERE id_aula IN ({$inPlaceholders}) AND activo = 1 ORDER BY nombre ASC");
        $stmtSel->execute(array_values($aulasSeleccionadasIds));
        $aulasDestino = $stmtSel->fetchAll(PDO::FETCH_ASSOC);

        if (empty($aulasDestino)) {
            http_response_code(404);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Ninguna de las aulas seleccionadas se encuentra activa.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } elseif ($idAulaManual) {
        // Aula manual específica solicitada
        $stmtA = $db->prepare("SELECT id_aula, nombre, codigo_sala, codigo FROM aulas WHERE id_aula = :aula AND activo = 1 LIMIT 1");
        $stmtA->execute([':aula' => $idAulaManual]);
        $aulaEncontrada = $stmtA->fetch(PDO::FETCH_ASSOC);
        if ($aulaEncontrada) {
            $aulasDestino[] = $aulaEncontrada;
        } else {
            http_response_code(404);
            echo json_encode([
                'status'  => 'error',
                'message' => 'El aula especificada no existe o no se encuentra activa.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } elseif ($tipo === 'estudiante') {
        // Aula correspondiente al estudiante (directa o por su grado)
        $stmtEstAula = $db->prepare("
            SELECT COALESCE(e.id_aula, g.id_aula) AS id_aula, a.nombre, a.codigo_sala, a.codigo
            FROM estudiantes e
            LEFT JOIN grados g ON g.id_grado = e.id_grado
            LEFT JOIN aulas a ON a.id_aula = COALESCE(e.id_aula, g.id_aula) AND a.activo = 1
            WHERE e.id_estudiante = :id
        ");
        $stmtEstAula->execute([':id' => $idPersona]);
        $aulaEst = $stmtEstAula->fetch(PDO::FETCH_ASSOC);

        if ($aulaEst && !empty($aulaEst['id_aula'])) {
            $aulasDestino[] = $aulaEst;
        } else {
            // Fallback: verificar si hay aula por defecto (laboratorio / id 18)
            $stmtDef = $db->prepare("SELECT id_aula, nombre, codigo_sala, codigo FROM aulas WHERE id_aula = 18 AND activo = 1 LIMIT 1");
            $stmtDef->execute();
            $aulaDef = $stmtDef->fetch(PDO::FETCH_ASSOC);
            if ($aulaDef) {
                $aulasDestino[] = $aulaDef;
            }
        }
    } else {
        // Para profesores: aulas asignadas según sus horarios lectivos activos
        $stmtProfAulas = $db->prepare("
            SELECT DISTINCT h.id_aula, a.nombre, a.codigo_sala, a.codigo
            FROM horarios h
            INNER JOIN asignacion_docente ad ON ad.id_asignacion = h.id_asignacion
            INNER JOIN aulas a ON a.id_aula = h.id_aula AND a.activo = 1
            WHERE ad.id_profesor = :id AND h.activo = 1 AND h.id_aula IS NOT NULL
            ORDER BY a.nombre ASC
        ");
        $stmtProfAulas->execute([':id' => $idPersona]);
        $aulasDestino = $stmtProfAulas->fetchAll(PDO::FETCH_ASSOC);

        // Si no tiene horarios cargados todavía, fallback a aulas que tienen nodos activos
        if (empty($aulasDestino)) {
            $stmtNodosAulas = $db->prepare("
                SELECT DISTINCT a.id_aula, a.nombre, a.codigo_sala, a.codigo
                FROM aulas a
                INNER JOIN nodos_esp32 n ON n.id_aula = a.id_aula AND n.activo = 1
                WHERE a.activo = 1
                ORDER BY a.nombre ASC
            ");
            $stmtNodosAulas->execute();
            $aulasDestino = $stmtNodosAulas->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    if (empty($aulasDestino)) {
        http_response_code(422);
        echo json_encode([
            'status'  => 'error',
            'message' => "No se encontraron aulas destino activas para {$nombreCompleto}."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 5. Encolar en sync_biometrica para cada aula
    $db->beginTransaction();

    $stmtCheckSync = $db->prepare("
        SELECT id_sync, estado 
        FROM sync_biometrica 
        WHERE id_huella = :huella AND id_aula = :aula 
        ORDER BY id_sync DESC 
        LIMIT 1
    ");

    $stmtCheckHardware = $db->prepare("
        SELECT id_aula_sync, slot_local, estado 
        FROM aula_huellas_sync 
        WHERE id_huella = :huella AND id_aula = :aula AND UPPER(estado) = 'CONFIRMADO'
        LIMIT 1
    ");

    $stmtUpdateSync = $db->prepare("
        UPDATE sync_biometrica 
        SET estado = 'PENDIENTE', intentos = 0, mensaje = NULL, fecha_actualizacion = NOW() 
        WHERE id_sync = :id
    ");

    $stmtInsertSync = $db->prepare("
        INSERT INTO sync_biometrica (
            id_huella, id_aula, room_id, estado, intentos, fecha_creacion, fecha_actualizacion
        ) VALUES (
            :huella, :aula, :room_id, 'PENDIENTE', 0, NOW(), NOW()
        )
    ");

    $detallesAulas = [];
    $aulasEncoladasEfectivas = [];

    foreach ($aulasDestino as $aula) {
        $idAulaActual = (int)$aula['id_aula'];
        $roomId = !empty($aula['codigo_sala']) ? $aula['codigo_sala'] : (!empty($aula['codigo']) ? $aula['codigo'] : null);
        $nombreAula = !empty($aula['nombre']) ? $aula['nombre'] : "Aula #{$idAulaActual}";

        // Si no se fuerza reenvío, verificar si ya está confirmada en el sensor DY50 de esa aula
        if (!$forzarReenvio) {
            $stmtCheckHardware->execute([':huella' => $idHuella, ':aula' => $idAulaActual]);
            $yaEnSensor = $stmtCheckHardware->fetch(PDO::FETCH_ASSOC);
            if ($yaEnSensor) {
                $detallesAulas[] = [
                    'id_aula'     => $idAulaActual,
                    'nombre'      => $nombreAula,
                    'codigo_sala' => $roomId,
                    'accion'      => 'omitido_ya_sincronizado',
                    'slot_local'  => (int)$yaEnSensor['slot_local']
                ];
                continue;
            }
        }

        $stmtCheckSync->execute([
            ':huella' => $idHuella,
            ':aula'   => $idAulaActual
        ]);
        $syncExistente = $stmtCheckSync->fetch(PDO::FETCH_ASSOC);

        if ($syncExistente) {
            $stmtUpdateSync->execute([':id' => $syncExistente['id_sync']]);
            $detallesAulas[] = [
                'id_aula'     => $idAulaActual,
                'nombre'      => $nombreAula,
                'codigo_sala' => $roomId,
                'id_sync'     => (int)$syncExistente['id_sync'],
                'accion'      => 'reencolado'
            ];
            $aulasEncoladasEfectivas[] = $idAulaActual;
        } else {
            $stmtInsertSync->execute([
                ':huella'  => $idHuella,
                ':aula'    => $idAulaActual,
                ':room_id' => $roomId
            ]);
            $detallesAulas[] = [
                'id_aula'     => $idAulaActual,
                'nombre'      => $nombreAula,
                'codigo_sala' => $roomId,
                'accion'      => 'nuevo'
            ];
            $aulasEncoladasEfectivas[] = $idAulaActual;
        }
    }

    // Marcar pendiente_sync en la entidad y en huellas_templates
    $db->prepare("UPDATE huellas_templates SET pendiente_sync = 1 WHERE id_huella = :id")->execute([':id' => $idHuella]);
    $db->prepare("UPDATE {$tabla} SET pendiente_sync = 1 WHERE {$idCampo} = :id")->execute([':id' => $idPersona]);

    $db->commit();

    // 6. Notificar al Gateway LoRa para iniciar transmisión dirigida a las aulas
    $aulaDestinoDirecto = (count($aulasEncoladasEfectivas) === 1) ? (int)$aulasEncoladasEfectivas[0] : null;
    $gatewayNotificado = !empty($aulasEncoladasEfectivas) ? avisarGatewaySync($idHuella, $aulaDestinoDirecto) : false;

    $nombresAulasStr = implode(', ', array_map(fn($a) => $a['nombre'], $detallesAulas));
    $cantAulas = count($detallesAulas);
    $cantEncoladas = count($aulasEncoladasEfectivas);

    $msgGateway = "";
    if ($cantEncoladas === 0) {
        $msgGateway = " Todas las aulas seleccionadas ya cuentan con la huella confirmada en el sensor DY50.";
    } elseif ($gatewayNotificado) {
        $msgGateway = " Gateway notificado para transmitir Huella #{$idHuella} vía LoRa a {$cantEncoladas} aula(s).";
    } else {
        $msgGateway = " Encolado en cola local (el Gateway sincronizará al consultar pendientes).";
    }

    http_response_code(200);
    echo json_encode([
        'status'  => 'success',
        'message' => "Huella de {$nombreCompleto} encolada exitosamente para {$cantAulas} aula(s): {$nombresAulasStr}.{$msgGateway}",
        'data'    => [
            'id_persona'         => $idPersona,
            'nombre'             => $nombreCompleto,
            'tipo_usuario'       => $tipo,
            'id_huella'          => $idHuella,
            'total_aulas'        => $cantAulas,
            'aulas'              => $detallesAulas,
            'gateway_notificado' => $gatewayNotificado
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error en Huella/enviar_huella_aulas: " . $e->getMessage());
    http_response_code(500);

    $modo = defined('MODO_ENTORNO') ? MODO_ENTORNO : 'cloud';
    $mensaje = ($modo === 'local')
        ? 'Error al enviar huella a aulas: ' . $e->getMessage()
        : 'Error interno al procesar el envío de huella a las aulas.';

    echo json_encode([
        'status'  => 'error',
        'message' => $mensaje
    ], JSON_UNESCAPED_UNICODE);
}

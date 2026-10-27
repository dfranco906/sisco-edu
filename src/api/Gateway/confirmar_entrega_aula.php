<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/biometria.php';

$receivedKey = '';
if (function_exists('getallheaders')) {
    $headers = getallheaders();
    $receivedKey = $headers['X-GATEWAY-KEY'] ?? ($headers['x-gateway-key'] ?? '');
}
if (empty($receivedKey)) {
    $receivedKey = $_SERVER['HTTP_X_GATEWAY_KEY'] ?? '';
}

if ($receivedKey !== GATEWAY_API_KEY) {
    http_response_code(404);
    echo json_encode(["status" => "not_found"]);
    exit;
}

$rawSync = $_POST['id_sync'] ?? filter_input(INPUT_POST, 'id_sync');
$rawHuella = $_POST['id_huella'] ?? filter_input(INPUT_POST, 'id_huella');
$rawAula = $_POST['id_aula'] ?? filter_input(INPUT_POST, 'id_aula');
$rawSlot = $_POST['slot_local'] ?? filter_input(INPUT_POST, 'slot_local');

$id_sync = filter_var($rawSync, FILTER_VALIDATE_INT) ?: null;
$id_huella = filter_var($rawHuella, FILTER_VALIDATE_INT) ?: null;
$id_aula = filter_var($rawAula, FILTER_VALIDATE_INT) ?: null;
$slot = filter_var($rawSlot, FILTER_VALIDATE_INT) ?: null;
$ci = trim($_POST['ci'] ?? '');
$tipo = strtolower(trim($_POST['tipo_persona'] ?? ''));
$crc32 = strtolower(trim($_POST['crc32'] ?? ''));

if (!$id_sync || !$id_huella || !$id_aula || !$slot || !$ci
    || !in_array($tipo, ['estudiante', 'profesor'], true)
    || preg_match('/\A[0-9a-f]{8}\z/D', $crc32) !== 1) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Confirmación inválida"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("
        SELECT s.id_aula, h.user_id_global, h.fingerprint_data, h.formato,
               e.cedula_identidad AS ci_estudiante, p.cedula_identidad AS ci_profesor
        FROM sync_biometrica s 
        INNER JOIN huellas_templates h ON h.id_huella = s.id_huella AND h.activo = 1
        LEFT JOIN estudiantes e ON e.activo = 1
            AND (e.id_estudiante = h.id_estudiante OR (h.id_estudiante IS NULL AND e.user_id_global = h.user_id_global))
        LEFT JOIN profesores p ON p.activo = 1
            AND (p.id_profesor = h.id_profesor OR (h.id_profesor IS NULL AND p.user_id_global = h.user_id_global))
        WHERE s.id_sync = :id_sync AND s.id_huella = :id_huella 
        LIMIT 1
    ");
    $stmt->execute([':id_sync' => $id_sync, ':id_huella' => $id_huella]);
    $sync = $stmt->fetch(PDO::FETCH_ASSOC);

    $ciEsperada = $tipo === 'estudiante' ? ($sync['ci_estudiante'] ?? null) : ($sync['ci_profesor'] ?? null);
    $templateValido = $sync
        && strtoupper((string)$sync['formato']) === 'HEX'
        && es_template_huella_hex_valido(trim((string)$sync['fingerprint_data']));

    if (!$sync || !$templateValido || (int)$sync['id_aula'] !== $id_aula || $ciEsperada !== $ci) {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "Confirmación no coincide con la sincronización programada"]);
        exit;
    }

    $templateBinario = hex2bin(trim((string)$sync['fingerprint_data']));
    $crcEsperado = $templateBinario === false ? '' : strtolower(hash('crc32b', $templateBinario));
    if ($crcEsperado === '' || !hash_equals($crcEsperado, $crc32)) {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "CRC32 del aula no coincide con el template central"]);
        exit;
    }

    $db->beginTransaction();

    // 1. Registrar o actualizar confirmación en aula_huellas_sync
    $stmtCheck = $db->prepare("SELECT id FROM aula_huellas_sync WHERE id_sync = :id_sync LIMIT 1");
    $stmtCheck->execute([':id_sync' => $id_sync]);
    $existingId = $stmtCheck->fetchColumn();

    if ($existingId) {
        $db->prepare("
            UPDATE aula_huellas_sync
            SET id_huella = :id_huella, id_aula = :id_aula, slot_local = :slot, ci = :ci, tipo_persona = :tipo, estado = 'RECIBIDO'
            WHERE id_sync = :id_sync
        ")->execute([':id_sync' => $id_sync, ':id_huella' => $id_huella, ':id_aula' => $id_aula, ':slot' => $slot, ':ci' => $ci, ':tipo' => $tipo]);
    } else {
        $db->prepare("
            INSERT INTO aula_huellas_sync (id_sync, id_huella, id_aula, ci, slot_local, tipo_persona, estado)
            VALUES (:id_sync, :id_huella, :id_aula, :ci, :slot, :tipo, 'RECIBIDO')
        ")->execute([':id_sync' => $id_sync, ':id_huella' => $id_huella, ':id_aula' => $id_aula, ':ci' => $ci, ':slot' => $slot, ':tipo' => $tipo]);
    }

    // 2. Marcar la sincronización como CONFIRMADO
    $db->prepare("
        UPDATE sync_biometrica 
        SET estado = 'CONFIRMADO', mensaje = :mensaje, fecha_actualizacion = NOW() 
        WHERE id_sync = :id
    ")->execute([':id' => $id_sync, ':mensaje' => 'Huella instalada y verificada por aula. CRC32=' . $crc32]);

    // 3. Marcar el template como sincronizado y registrar slot
    $db->prepare("
        UPDATE huellas_templates 
        SET pendiente_sync = 0, sensor_slot = :slot 
        WHERE id_huella = :id
    ")->execute([':slot' => $slot, ':id' => $id_huella]);

    if ($tipo === 'estudiante') {
        $db->prepare("UPDATE estudiantes SET pendiente_sync = 0 WHERE huella_id = :id")->execute([':id' => $id_huella]);
    } else {
        $db->prepare("UPDATE profesores SET pendiente_sync = 0 WHERE huella_id = :id")->execute([':id' => $id_huella]);
    }

    // 4. Mapeo biométrico activo por aula
    if (!empty($sync['user_id_global'])) {
        $stmtCod = $db->prepare("SELECT codigo FROM aulas WHERE id_aula = :id LIMIT 1");
        $stmtCod->execute([':id' => $id_aula]);
        $roomCode = $stmtCod->fetchColumn() ?: (string)$id_aula;

        $db->prepare("UPDATE biometric_mapping SET activo = 0 WHERE user_id_global = :uid AND (id_aula = :aula OR room_id = :room)")
           ->execute([':uid' => $sync['user_id_global'], ':aula' => $id_aula, ':room' => $roomCode]);

        $stmtMap = $db->prepare("
            INSERT INTO biometric_mapping (user_id_global, id_aula, room_id, sensor_slot, activo)
            VALUES (:uid, :aula, :room, :slot, 1)
        ");
        $stmtMap->execute([
            ':uid'  => $sync['user_id_global'],
            ':aula' => $id_aula,
            ':room' => $roomCode,
            ':slot' => $slot
        ]);
    }

    $db->commit();
    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Entrega confirmada y huella activa en el nodo del aula"]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error en Gateway/confirmar_entrega_aula: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al confirmar entrega de huella"]);
}

<?php
// src/api/Gateway/obtener_sync_aula.php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/biometria.php';

// Validar cabecera de seguridad
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

$rawAula = $_GET['id_aula'] ?? null;
$rawHuella = $_GET['id_huella'] ?? null;
$id_aula = filter_var($rawAula, FILTER_VALIDATE_INT) ?: null;
$id_huella = filter_var($rawHuella, FILTER_VALIDATE_INT) ?: null;

if (!$id_aula && !$id_huella) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Falta id_aula o id_huella valido"]);
    exit;
}

try {
    $db = (new Database())->getConnection();

    $condiciones = ["s.estado = 'PENDIENTE'"];
    $params = [];

    if ($id_aula) {
        $condiciones[] = "s.id_aula = :id_aula";
        $params[':id_aula'] = $id_aula;
    }
    if ($id_huella) {
        $condiciones[] = "s.id_huella = :id_huella";
        $params[':id_huella'] = $id_huella;
    }
    $whereClause = implode(" AND ", $condiciones);

    $stmt = $db->prepare("
        SELECT s.id_sync, s.id_huella, s.id_aula, h.user_id_global,
               h.fingerprint_data AS huella_hex, h.formato,
               e.id_estudiante, e.cedula_identidad AS ci_estudiante,
               p.id_profesor, p.cedula_identidad AS ci_profesor,
               n.lora_id
        FROM sync_biometrica s
        INNER JOIN huellas_templates h ON h.id_huella = s.id_huella AND h.activo = 1
        LEFT JOIN nodos_esp32 n ON n.id_aula = s.id_aula AND n.activo = 1
        LEFT JOIN estudiantes e ON e.activo = 1
            AND (e.id_estudiante = h.id_estudiante OR (h.id_estudiante IS NULL AND e.user_id_global = h.user_id_global))
        LEFT JOIN profesores p ON p.activo = 1
            AND (p.id_profesor = h.id_profesor OR (h.id_profesor IS NULL AND p.user_id_global = h.user_id_global))
        WHERE {$whereClause}
        ORDER BY s.id_sync ASC 
        LIMIT 1
    ");
    $stmt->execute($params);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        echo json_encode(["status" => "empty", "message" => "No hay sincronizaciones pendientes"]);
        exit;
    }

    $tipo = $data['id_estudiante'] !== null ? 'estudiante' : ($data['id_profesor'] !== null ? 'profesor' : null);
    $ci = $tipo === 'estudiante' ? $data['ci_estudiante'] : $data['ci_profesor'];
    $hex = trim((string)$data['huella_hex']);

    if (!$tipo || !$ci || strtoupper((string)$data['formato']) !== 'HEX' || !es_template_huella_hex_valido($hex)) {
        $db->prepare("UPDATE sync_biometrica SET estado='ERROR', mensaje='Template o persona invalida', fecha_actualizacion=NOW() WHERE id_sync=:id")
           ->execute([':id' => $data['id_sync']]);
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "Sync invalida"]);
        exit;
    }

    $db->prepare("UPDATE sync_biometrica SET estado='ENVIADO', intentos=intentos+1, fecha_actualizacion=NOW() WHERE id_sync=:id AND estado='PENDIENTE'")
       ->execute([':id' => $data['id_sync']]);

    echo json_encode([
        "status" => "success",
        "data"   => [
            "id_sync"       => (int)$data['id_sync'],
            "id_huella"     => (int)$data['id_huella'],
            "id_aula"       => (int)$data['id_aula'],
            "lora_id"       => !empty($data['lora_id']) ? (int)$data['lora_id'] : 0,
            "tipo_persona"  => $tipo,
            "ci"            => $ci,
            "huella_base64" => $hex,
            "formato"       => "HEX",
            "bytes"         => HUELLA_TEMPLATE_BYTES
        ]
    ]);
} catch (Throwable $e) {
    error_log("Error en obtener_sync_aula.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al consultar huella"]);
}

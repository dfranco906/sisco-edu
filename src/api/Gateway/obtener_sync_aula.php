<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/app.php';

if ((getallheaders()['X-GATEWAY-KEY'] ?? '') !== GATEWAY_API_KEY) {
    http_response_code(404); echo json_encode(["status" => "not_found"]); exit;
}
$id_aula = filter_input(INPUT_GET, 'id_aula', FILTER_VALIDATE_INT);
if (!$id_aula) { http_response_code(400); echo json_encode(["status" => "error", "message" => "Falta id_aula valido"]); exit; }
$db = (new Database())->getConnection();
$stmt = $db->prepare("SELECT s.id_sync, s.id_huella, s.id_aula, h.user_id_global,
        h.fingerprint_data AS huella_hex, h.formato,
        e.id_estudiante, e.cedula_identidad AS ci_estudiante,
        p.id_profesor, p.cedula_identidad AS ci_profesor
    FROM sync_biometrica s
    INNER JOIN huellas_templates h ON h.id_huella = s.id_huella AND h.activo = 1
    LEFT JOIN estudiantes e ON e.user_id_global = h.user_id_global AND e.activo = 1
    LEFT JOIN profesores p ON p.user_id_global = h.user_id_global AND p.activo = 1
    WHERE s.estado = 'PENDIENTE' AND s.id_aula = :id_aula
    ORDER BY s.id_sync ASC LIMIT 1");
$stmt->execute([':id_aula' => $id_aula]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) { echo json_encode(["status" => "empty", "message" => "No hay sincronizaciones pendientes"]); exit; }
$tipo = $data['id_estudiante'] !== null ? 'estudiante' : ($data['id_profesor'] !== null ? 'profesor' : null);
$ci = $tipo === 'estudiante' ? $data['ci_estudiante'] : $data['ci_profesor'];
$hex = trim((string)$data['huella_hex']);
if (!$tipo || !$ci || strtoupper((string)$data['formato']) !== 'HEX' || !preg_match('/^[0-9a-fA-F]{2816}$/', $hex)) {
    $db->prepare("UPDATE sync_biometrica SET estado='ERROR', mensaje='Template o persona invalida', fecha_actualizacion=NOW() WHERE id_sync=:id")
       ->execute([':id' => $data['id_sync']]);
    http_response_code(422); echo json_encode(["status" => "error", "message" => "Sync invalida"]); exit;
}
$db->prepare("UPDATE sync_biometrica SET estado='ENVIADO', intentos=intentos+1, fecha_actualizacion=NOW() WHERE id_sync=:id AND estado='PENDIENTE'")
   ->execute([':id' => $data['id_sync']]);
echo json_encode(["status" => "success", "data" => [
    "id_sync" => (int)$data['id_sync'], "id_huella" => (int)$data['id_huella'], "id_aula" => (int)$data['id_aula'],
    "tipo_persona" => $tipo, "ci" => $ci, "huella_base64" => $hex, "formato" => "HEX"
]]);
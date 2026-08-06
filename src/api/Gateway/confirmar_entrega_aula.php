<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/app.php';
if ((getallheaders()['X-GATEWAY-KEY'] ?? '') !== GATEWAY_API_KEY) { http_response_code(404); echo json_encode(["status" => "not_found"]); exit; }
$id_sync = filter_input(INPUT_POST, 'id_sync', FILTER_VALIDATE_INT);
$id_huella = filter_input(INPUT_POST, 'id_huella', FILTER_VALIDATE_INT);
$id_aula = filter_input(INPUT_POST, 'id_aula', FILTER_VALIDATE_INT);
$slot = filter_input(INPUT_POST, 'slot_local', FILTER_VALIDATE_INT);
$ci = trim($_POST['ci'] ?? ''); $tipo = strtolower(trim($_POST['tipo_persona'] ?? ''));
if (!$id_sync || !$id_huella || !$id_aula || !$slot || !$ci || !in_array($tipo, ['estudiante', 'profesor'], true)) { http_response_code(400); echo json_encode(["status" => "error", "message" => "Confirmacion invalida"]); exit; }
try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT s.id_aula, h.user_id_global,
        e.cedula_identidad AS ci_estudiante, p.cedula_identidad AS ci_profesor
        FROM sync_biometrica s INNER JOIN huellas_templates h ON h.id_huella=s.id_huella
        LEFT JOIN estudiantes e ON e.user_id_global=h.user_id_global AND e.activo=1
        LEFT JOIN profesores p ON p.user_id_global=h.user_id_global AND p.activo=1
        WHERE s.id_sync=:id_sync AND s.id_huella=:id_huella LIMIT 1");
    $stmt->execute([':id_sync'=>$id_sync, ':id_huella'=>$id_huella]); $sync = $stmt->fetch(PDO::FETCH_ASSOC);
    $ciEsperada = $tipo === 'estudiante' ? ($sync['ci_estudiante'] ?? null) : ($sync['ci_profesor'] ?? null);
    if (!$sync || (int)$sync['id_aula'] !== $id_aula || $ciEsperada !== $ci) { http_response_code(422); echo json_encode(["status"=>"error", "message"=>"Confirmacion no coincide con la sincronizacion"]); exit; }
    $db->beginTransaction();
    $db->prepare("INSERT INTO aula_huellas_sync (id_sync,id_huella,id_aula,ci,slot_local,tipo_persona,estado)
        VALUES (:id_sync,:id_huella,:id_aula,:ci,:slot,:tipo,'RECIBIDO')
        ON DUPLICATE KEY UPDATE slot_local=VALUES(slot_local), ci=VALUES(ci), tipo_persona=VALUES(tipo_persona), estado='RECIBIDO'")
      ->execute([':id_sync'=>$id_sync, ':id_huella'=>$id_huella, ':id_aula'=>$id_aula, ':ci'=>$ci, ':slot'=>$slot, ':tipo'=>$tipo]);
    $db->prepare("UPDATE sync_biometrica SET estado='CONFIRMADO', mensaje='Huella instalada y confirmada por aula', fecha_actualizacion=NOW() WHERE id_sync=:id")
      ->execute([':id'=>$id_sync]);
    $db->prepare("UPDATE huellas_templates SET pendiente_sync=0 WHERE id_huella=:id")->execute([':id'=>$id_huella]);
    $db->commit(); echo json_encode(["status"=>"success", "message"=>"Entrega confirmada"]);
} catch (Throwable $e) { if (isset($db) && $db->inTransaction()) $db->rollBack(); http_response_code(500); echo json_encode(["status"=>"error", "message"=>"Error al confirmar entrega"]); }
<?php
require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../../config/device_auth.php';
requireDevice('POST');
$id=filter_input(INPUT_POST,'id_sync',FILTER_VALIDATE_INT);
$estado=(string)($_POST['estado'] ?? '');
if (!$id || $estado !== 'ERROR') {
    http_response_code(422); echo json_encode(['ok'=>false,'status'=>'error','message'=>'Usar confirmar_entrega_aula para confirmar instalación']); exit;
}
$db=(new Database())->getConnection();
$db->prepare("UPDATE sync_biometrica SET estado=IF(COALESCE(intentos,0)>=5,'ERROR','PENDIENTE'), mensaje=?, fecha_actualizacion=NOW() WHERE id_sync=? AND estado='ENVIADO'")
    ->execute([substr((string)($_POST['mensaje'] ?? ''),0,255),$id]);
echo json_encode(['status'=>'success']);

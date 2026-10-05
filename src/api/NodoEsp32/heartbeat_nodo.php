<?php
require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../../config/device_auth.php';
requireDevice('POST');
$node=trim((string)($_POST['node_id'] ?? ''));
if ($node === '' || strlen($node)>100) { http_response_code(422); echo json_encode(['ok'=>false]); exit; }
$db=(new Database())->getConnection();
$s=$db->prepare("SELECT n.id_nodo,n.tipo,n.lora_id,a.id_aula FROM nodos_esp32 n LEFT JOIN aulas a ON a.codigo=n.room_id AND a.activo=1 WHERE n.node_id=? AND n.activo=1");
$s->execute([$node]); $row=$s->fetch(PDO::FETCH_ASSOC);
if (!$row || ($row['tipo']==='AULA' && ((int)($_POST['id_aula'] ?? 0)!==(int)$row['id_aula'] || (int)($_POST['lora_id'] ?? 0)!==(int)$row['lora_id']))) {
    http_response_code(422); echo json_encode(['ok'=>false,'status'=>'node_mismatch']); exit;
}
$db->prepare("UPDATE nodos_esp32 SET ultimo_heartbeat=NOW() WHERE id_nodo=?")->execute([$row['id_nodo']]);
echo json_encode(['ok'=>true,'status'=>'success']);

<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/app.php';
if ((getallheaders()['X-GATEWAY-KEY'] ?? '') !== GATEWAY_API_KEY) { http_response_code(404); echo json_encode(['status'=>'not_found']); exit; }
header('Content-Type: application/json; charset=UTF-8');
try{$db=(new Database())->getConnection();$stmt=$db->query("SELECT a.id_aula,a.codigo AS room_id,n.node_id,n.lora_id FROM nodos_esp32 n INNER JOIN aulas a ON a.codigo=n.room_id AND a.activo=1 WHERE n.tipo='AULA' AND n.activo=1 AND n.lora_id IS NOT NULL ORDER BY a.id_aula");echo json_encode(['status'=>'success','data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}
catch(Throwable $e){error_log('rutas_aulas_gateway: '.$e->getMessage());http_response_code(500);echo json_encode(['status'=>'error','message'=>'No se pudieron cargar las rutas.']);}

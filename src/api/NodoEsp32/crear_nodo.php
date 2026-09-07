<?php
require_once __DIR__ . '/../../config/api_auth.php';
require_once __DIR__ . '/../../classes/NodoEsp32.php';
requerirMetodo(['POST']); usuarioActual(['SuperAdmin','Administracion','Coordinador']);
try {
    $nodeId=trim((string)($_POST['node_id']??''));$roomId=trim((string)($_POST['room_id']??''));$tipo=strtoupper(trim((string)($_POST['tipo']??'AULA')));$estado=strtolower(trim((string)($_POST['estado']??'offline')));$loraId=filter_var($_POST['lora_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>65535]]);
    if($nodeId===''||strlen($nodeId)>100||!in_array($tipo,['AULA','GATEWAY','CAPTURA'],true)||!in_array($estado,['online','offline'],true))throw new InvalidArgumentException('Node ID, tipo y estado son obligatorios.');
    if($tipo==='AULA'&&($roomId===''||!$loraId))throw new InvalidArgumentException('Un nodo de aula requiere aula y dirección LoRa.');
    $db=(new Database())->getConnection();
    if($roomId!==''){$q=$db->prepare("SELECT COUNT(*) FROM aulas WHERE codigo=:codigo AND activo=1");$q->execute([':codigo'=>$roomId]);if(!(int)$q->fetchColumn())throw new InvalidArgumentException('El aula seleccionada no está activa.');}
    if($loraId){$q=$db->prepare("SELECT COUNT(*) FROM nodos_esp32 WHERE lora_id=:lora AND activo=1");$q->execute([':lora'=>$loraId]);if((int)$q->fetchColumn())throw new InvalidArgumentException('La dirección LoRa ya está asignada a otro nodo activo.');}
    $n=new NodoEsp32($db);$n->node_id=$nodeId;$n->room_id=$roomId?:null;$n->lora_id=$loraId?:null;$n->tipo=$tipo;$n->estado=$estado;if(!$n->crear())throw new RuntimeException('No se pudo insertar el nodo.');
    responderJson(['success'=>true,'status'=>'success','message'=>'Nodo ESP32 creado correctamente.','data'=>['id_nodo'=>(int)$db->lastInsertId()]],201);
}catch(InvalidArgumentException $e){responderJson(['success'=>false,'status'=>'error','message'=>$e->getMessage()],422);}catch(PDOException $e){error_log('crear_nodo: '.$e->getMessage());responderJson(['success'=>false,'status'=>'error','message'=>'El node ID ya existe o los datos no son válidos.'],409);}catch(Throwable $e){error_log('crear_nodo: '.$e->getMessage());responderJson(['success'=>false,'status'=>'error','message'=>'No se pudo crear el nodo.'],500);}

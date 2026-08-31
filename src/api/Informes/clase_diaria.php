<?php
require_once __DIR__ . '/../../config/api_auth.php';require_once __DIR__ . '/../../classes/ClaseDiaria.php';
requerirMetodo(['POST']);$usuario=usuarioActual(['SuperAdmin','Administracion','Coordinador','Profesor']);
try{$db=(new Database())->getConnection();$datos=entradaJson();$idAsignacion=enteroPositivo($datos['id_asignacion']??null,'Asignacion');asegurarAccesoAsignacion($db,$usuario,$idAsignacion,true);$s=new ClaseDiaria($db);$id=$s->crearManual($idAsignacion,(string)($datos['fecha']??''),empty($datos['id_horario'])?null:enteroPositivo($datos['id_horario'],'Horario'));responderJson(['success'=>true,'status'=>'success','message'=>'Clase creada o recuperada.','data'=>['id_clase'=>$id]]);}
catch(PedagogiaException $e){responderJson(['success'=>false,'status'=>'error','message'=>$e->getMessage()],$e->http);}catch(Throwable $e){error_log('clase_diaria: '.$e->getMessage());responderJson(['success'=>false,'status'=>'error','message'=>'No se pudo generar la clase diaria.'],500);}
?>

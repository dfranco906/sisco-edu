<?php
require_once __DIR__ . '/../../config/api_auth.php';
require_once __DIR__ . '/../../classes/PlanificacionPedagogica.php';
requerirMetodo(['GET']);
$usuario=usuarioActual(['SuperAdmin','Administracion','Coordinador','Profesor']);
try{$db=(new Database())->getConnection();$servicio=new PlanificacionPedagogica($db,$usuario);responderJson(['success'=>true,'status'=>'success','data'=>$servicio->asignacionesDisponibles()]);}
catch(PedagogiaException $e){responderJson(['success'=>false,'status'=>'error','message'=>$e->getMessage()],$e->http);}
catch(Throwable $e){error_log('planificacion_asignaciones: '.$e->getMessage());responderJson(['success'=>false,'status'=>'error','message'=>'No se pudieron cargar las asignaciones.'],500);}
?>

<?php
require_once __DIR__ . '/../../config/api_auth.php';
require_once __DIR__ . '/../../classes/PlanificacionPedagogica.php';
requerirMetodo(['POST']);
$usuario=usuarioActual(['SuperAdmin','Administracion','Coordinador','Profesor']);
try{
    $db=(new Database())->getConnection();$servicio=new PlanificacionPedagogica($db,$usuario);$datos=entradaJson();$accion=(string)($datos['accion']??'guardar');
    if($accion==='guardar'){$id=$servicio->guardarProgramacion($datos);responderJson(['success'=>true,'status'=>'success','message'=>'Programacion guardada.','data'=>['id_programacion'=>$id]]);}
    if($accion==='eliminar'){$servicio->eliminarProgramacion(enteroPositivo($datos['id_programacion']??null,'Programacion'));responderJson(['success'=>true,'status'=>'success','message'=>'Programacion eliminada.']);}
    throw new PedagogiaException('Accion no valida.');
}catch(PedagogiaException $e){responderJson(['success'=>false,'status'=>'error','message'=>$e->getMessage()],$e->http);}
catch(Throwable $e){error_log('programacion_plan: '.$e->getMessage());responderJson(['success'=>false,'status'=>'error','message'=>'No se pudo procesar la programacion.'],500);}
?>

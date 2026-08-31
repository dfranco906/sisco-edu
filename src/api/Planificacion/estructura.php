<?php
require_once __DIR__ . '/../../config/api_auth.php';
require_once __DIR__ . '/../../classes/PlanificacionPedagogica.php';
requerirMetodo(['POST']);
$usuario=usuarioActual(['SuperAdmin','Administracion','Coordinador','Profesor']);
try{
    $db=(new Database())->getConnection();$servicio=new PlanificacionPedagogica($db,$usuario);$datos=entradaJson();
    $tipo=strtolower(trim((string)($datos['tipo']??'')));$accion=strtolower(trim((string)($datos['accion']??'guardar')));
    if($accion==='guardar'){$id=$servicio->guardarElemento($tipo,$datos);responderJson(['success'=>true,'status'=>'success','message'=>'Contenido guardado.','data'=>['id'=>$id]]);}
    if($accion==='eliminar'){$id=enteroPositivo($datos['id']??null,'Registro');$r=$servicio->eliminarElemento($tipo,$id,filter_var($datos['confirmado']??false,FILTER_VALIDATE_BOOLEAN));responderJson(['success'=>true,'status'=>'success','message'=>'Contenido eliminado.','data'=>$r]);}
    if($accion==='reordenar'){$servicio->reordenar($tipo,is_array($datos['ids']??null)?$datos['ids']:[]);responderJson(['success'=>true,'status'=>'success','message'=>'Orden actualizado.']);}
    if($accion==='evaluacion'){$servicio->guardarEvaluacion(enteroPositivo($datos['id_tema']??null,'Tema'),is_array($datos['procedimientos']??null)?$datos['procedimientos']:[],is_array($datos['instrumentos']??null)?$datos['instrumentos']:[]);responderJson(['success'=>true,'status'=>'success','message'=>'Evaluacion actualizada.']);}
    throw new PedagogiaException('Accion no valida.');
}catch(PedagogiaException $e){responderJson(['success'=>false,'status'=>'error','message'=>$e->getMessage()],$e->http);}
catch(Throwable $e){error_log('estructura_plan: '.$e->getMessage());responderJson(['success'=>false,'status'=>'error','message'=>'No se pudo guardar la estructura.'],500);}
?>

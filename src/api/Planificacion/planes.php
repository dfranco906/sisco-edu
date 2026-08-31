<?php
require_once __DIR__ . '/../../config/api_auth.php';
require_once __DIR__ . '/../../classes/PlanificacionPedagogica.php';
requerirMetodo(['GET','POST']);
$usuario=usuarioActual(['SuperAdmin','Administracion','Coordinador','Profesor']);
try{
    $db=(new Database())->getConnection();$servicio=new PlanificacionPedagogica($db,$usuario);
    if($_SERVER['REQUEST_METHOD']==='GET'){
        if(!empty($_GET['id_plan']))$data=$servicio->obtenerPlan(enteroPositivo($_GET['id_plan'],'Plan'));
        else $data=$servicio->listar($_GET);
        responderJson(['success'=>true,'status'=>'success','data'=>$data]);
    }
    $datos=entradaJson();$accion=(string)($datos['accion']??'crear');
    if($accion==='crear'){$id=$servicio->crearPlan($datos);responderJson(['success'=>true,'status'=>'success','message'=>'Plan anual creado.','data'=>['id_plan'=>$id]],201);}
    $id=enteroPositivo($datos['id_plan']??null,'Plan');
    if($accion==='actualizar'){$servicio->actualizarPlan($id,$datos);$mensaje='Plan actualizado.';}
    elseif($accion==='estado'){$servicio->cambiarEstado($id,strtoupper(trim((string)($datos['estado']??''))));$mensaje='Estado del plan actualizado.';}
    else throw new PedagogiaException('Accion no valida.');
    responderJson(['success'=>true,'status'=>'success','message'=>$mensaje,'data'=>['id_plan'=>$id]]);
}catch(PedagogiaException $e){responderJson(['success'=>false,'status'=>'error','message'=>$e->getMessage()],$e->http);}
catch(Throwable $e){error_log('planes: '.$e->getMessage());responderJson(['success'=>false,'status'=>'error','message'=>'No se pudo procesar el plan.'],500);}
?>

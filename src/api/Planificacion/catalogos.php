<?php
require_once __DIR__ . '/../../config/api_auth.php';
require_once __DIR__ . '/../../classes/PlanificacionPedagogica.php';
requerirMetodo(['GET','POST']);
$usuario=usuarioActual(['SuperAdmin','Administracion','Coordinador','Profesor']);
try{
    $db=(new Database())->getConnection();$servicio=new PlanificacionPedagogica($db,$usuario);
    if($_SERVER['REQUEST_METHOD']==='GET')responderJson(['success'=>true,'status'=>'success','data'=>$servicio->catalogos()]);
    $datos=entradaJson();$item=$servicio->crearCatalogo(strtolower(trim((string)($datos['tipo']??''))),(string)($datos['nombre']??''));
    responderJson(['success'=>true,'status'=>'success','message'=>'Opcion creada.','data'=>$item],201);
}catch(PedagogiaException $e){responderJson(['success'=>false,'status'=>'error','message'=>$e->getMessage()],$e->http);}
catch(Throwable $e){error_log('catalogos_plan: '.$e->getMessage());responderJson(['success'=>false,'status'=>'error','message'=>'No se pudo procesar el catalogo.'],500);}
?>

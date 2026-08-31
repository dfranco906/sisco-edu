<?php
require_once __DIR__ . '/../../config/api_auth.php';require_once __DIR__ . '/../../classes/ClaseDiaria.php';
requerirMetodo(['GET']);$usuario=usuarioActual(['SuperAdmin','Administracion','Coordinador','Profesor']);
try{$db=(new Database())->getConnection();$id=enteroPositivo($_GET['id_asignacion']??null,'Asignacion');asegurarAccesoAsignacion($db,$usuario,$id,false);$s=new ClaseDiaria($db);responderJson(['success'=>true,'status'=>'success','data'=>$s->temasPublicados($id)]);}catch(PedagogiaException $e){responderJson(['success'=>false,'status'=>'error','message'=>$e->getMessage()],$e->http);}catch(Throwable $e){error_log('temas_publicados: '.$e->getMessage());responderJson(['success'=>false,'status'=>'error','message'=>'No se pudieron cargar los temas.'],500);}
?>

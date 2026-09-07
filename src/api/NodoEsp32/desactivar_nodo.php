<?php
require_once __DIR__ . '/../../config/api_auth.php'; require_once __DIR__ . '/../../classes/NodoEsp32.php';
requerirMetodo(['POST']); usuarioActual(['SuperAdmin','Administracion','Coordinador']);
try{$id=filter_var($_POST['id_nodo']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if(!$id)responderJson(['success'=>false,'status'=>'error','message'=>'Nodo no válido.'],422);$db=(new Database())->getConnection();$n=new NodoEsp32($db);$n->id_nodo=$id;$ok=$n->desactivar();responderJson(['success'=>$ok,'status'=>$ok?'success':'error','message'=>$ok?'Nodo desactivado correctamente.':'No se encontró el nodo.']);}catch(Throwable $e){error_log('desactivar_nodo: '.$e->getMessage());responderJson(['success'=>false,'status'=>'error','message'=>'No se pudo desactivar el nodo.'],500);}

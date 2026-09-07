<?php
require_once __DIR__ . '/../../config/api_auth.php'; require_once __DIR__ . '/../../classes/NodoEsp32.php';
requerirMetodo(['GET']); usuarioActual(['SuperAdmin','Administracion','Coordinador']);
try{$db=(new Database())->getConnection();$stmt=(new NodoEsp32($db))->leer();responderJson(['success'=>true,'status'=>'success','data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);}catch(Throwable $e){error_log('leer_nodos: '.$e->getMessage());responderJson(['success'=>false,'status'=>'error','message'=>'No se pudieron cargar los nodos.','data'=>[]],500);}

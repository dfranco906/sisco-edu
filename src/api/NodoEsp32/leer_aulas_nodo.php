<?php
require_once __DIR__ . '/../../config/api_auth.php';
requerirMetodo(['GET']);
usuarioActual(['SuperAdmin','Administracion','Coordinador']);
try{$db=(new Database())->getConnection();$stmt=$db->query("SELECT id_aula,nombre,codigo,ubicacion FROM aulas WHERE activo=1 ORDER BY nombre");responderJson(['success'=>true,'status'=>'success','data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);}
catch(Throwable $e){error_log('leer_aulas_nodo: '.$e->getMessage());responderJson(['success'=>false,'status'=>'error','message'=>'No se pudieron cargar las aulas.'],500);}

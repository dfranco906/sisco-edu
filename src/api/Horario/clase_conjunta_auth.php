<?php
require_once __DIR__.'/../../config/api_auth.php';

function autorizarMateriasDistintas(): array
{
    $usuario=usuarioActual(['SuperAdmin','Administracion','Coordinador','Profesor']);
    $token=$_POST['horarios_csrf']??$_SERVER['HTTP_X_CSRF_TOKEN']??'';
    if(!is_string($token)||!isset($_SESSION['horarios_csrf'])||!hash_equals($_SESSION['horarios_csrf'],$token)) {
        responderJson(['success'=>false,'status'=>'error','message'=>'El formulario venció. Recargue Horarios e intente nuevamente.'],403);
    }
    return $usuario;
}

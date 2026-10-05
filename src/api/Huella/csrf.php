<?php
require_once __DIR__.'/../../config/api_auth.php';
requerirMetodo(['GET']); usuarioActual(['SuperAdmin','Administracion','Coordinador']);
$_SESSION['biometric_csrf'] ??= bin2hex(random_bytes(32));
header('Cache-Control: no-store');
responderJson(['status'=>'success','csrf'=>$_SESSION['biometric_csrf']]);

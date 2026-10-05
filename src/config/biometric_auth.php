<?php
require_once __DIR__.'/device_auth.php';
require_once __DIR__.'/api_auth.php';
function requireBiometricWriter(): void
{
    requerirMetodo(['POST']);
    if (deviceAuthenticated()) return;
    usuarioActual(['SuperAdmin','Administracion','Coordinador']);
    $expected=(string)($_SESSION['biometric_csrf'] ?? '');
    $actual=deviceHeader('X-CSRF-Token');
    if ($expected === '' || !hash_equals($expected,$actual)) responderJson(['ok'=>false,'status'=>'error','message'=>'CSRF inválido'],403);
}

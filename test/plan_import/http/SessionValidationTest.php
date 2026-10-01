<?php
declare(strict_types=1);
require_once __DIR__.'/../support/TestEnvironment.php';
require_once dirname(__DIR__,3).'/src/config/session_identity.php';
$env = new PlanImportTestEnvironment();
$checks = 0;
function sessionCheck(bool $condition, string $message): void {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
try {
    foreach ([[], ['id_usuario'=>null], ['id_usuario'=>0], ['id_usuario'=>'0'],
        ['id_usuario'=>-1], ['id_usuario'=>'-1'], ['id_usuario'=>true],
        ['id_usuario'=>1.5], ['id_usuario'=>[]], ['id_usuario'=>'1abc'],
        ['id_usuario'=>PHP_INT_MAX]] as $invalid) {
        sessionCheck(validarIdentidadSesion($env->db, $invalid+['rol'=>'Profesor'])===null, 'Identidad invalida aceptada');
    }
    foreach ([$env->user, (string)$env->user] as $id) {
        sessionCheck(validarIdentidadSesion($env->db, ['id_usuario'=>$id,'rol'=>'Profesor'])===['id_usuario'=>$env->user,'rol'=>'Profesor'], 'Login valido rechazado');
    }
    sessionCheck(validarIdentidadSesion($env->db, ['id_usuario'=>$env->user,'rol'=>'SuperAdmin'])===null, 'Rol obsoleto aceptado');
    $env->db->prepare('UPDATE usuarios SET activo=0 WHERE id_usuario=?')->execute([$env->user]);
    sessionCheck(validarIdentidadSesion($env->db, ['id_usuario'=>$env->user,'rol'=>'Profesor'])===null, 'Usuario inactivo aceptado');
    $env->db->prepare('UPDATE usuarios SET activo=1 WHERE id_usuario=?')->execute([$env->user]);
    // Real endpoint in the isolated PHP -S environment: regression, not Apache proof.
    foreach ([null, 0, -1, PHP_INT_MAX] as $invalidId) {
        session_id($env->session); session_start();
        $_SESSION=['id_usuario'=>$invalidId,'rol'=>'Profesor','plan_import_csrf'=>$env->csrf];
        session_write_close();
        $r=$env->request('importar_plan_pdf.php','POST',[]);
        sessionCheck($r['status']===401, 'La API debe rechazar la sesion antes del importador');
    }
    session_id($env->session); session_start();
    $_SESSION=['id_usuario'=>(string)$env->user,'rol'=>'Profesor','plan_import_csrf'=>$env->csrf];
    session_write_close();
    $r=$env->request('asignaciones.php');
    sessionCheck($r['status']===200, 'Sesion positiva activa debe seguir funcionando');
    $env->db->prepare('UPDATE usuarios SET activo=0 WHERE id_usuario=?')->execute([$env->user]);
    sessionCheck($env->request('asignaciones.php')['status']===401, 'La API acepta usuario desactivado');
    echo "OK | SessionValidationTest | $checks checks (BD temporal; no evidencia de Apache)\n";
} finally { $env->close(); }

<?php
declare(strict_types=1);
require_once __DIR__.'/../support/TestEnvironment.php';
$env=new PlanImportTestEnvironment();
function loginRequest(PlanImportTestEnvironment $env, string $username): array {
    $handle=curl_init($env->url.'/src/api/login.php');
    curl_setopt_array($handle,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query(['usuario'=>$username,'password'=>'Temporal123']),CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>15]);
    $raw=curl_exec($handle); $status=(int)curl_getinfo($handle,CURLINFO_RESPONSE_CODE); curl_close($handle);
    preg_match('/^Location:\s*(.+)$/mi',(string)$raw,$match);
    return ['status'=>$status,'location'=>trim($match[1]??'')];
}
try {
    $valid=loginRequest($env,'pdf_test');
    if($valid['status']!==302||!str_ends_with($valid['location'],'dashboard.php'))throw new RuntimeException('Login positivo valido roto');
    // Explicit zero identity only in this disposable database, matching the audited defect.
    $env->db->exec("SET SESSION sql_mode=CONCAT(@@sql_mode,',NO_AUTO_VALUE_ON_ZERO')");
    $q=$env->db->prepare("INSERT INTO usuarios (id_usuario,nombre,apellido,usuario,email,celular,password,rol,activo) VALUES (0,'Zero','Test','zero_identity','zero@test.local',981234569,?,'SuperAdmin',1)");
    $q->execute([password_hash('Temporal123',PASSWORD_DEFAULT)]);
    $zero=loginRequest($env,'zero_identity');
    if($zero['status']!==302||!str_ends_with($zero['location'],'login.php?error=sesion'))throw new RuntimeException('Login ID cero aceptado');
    echo "OK | SessionLoginTest (login valido conservado; ID 0 rechazado; BD temporal)\n";
} finally { $env->close(); }

<?php
declare(strict_types=1);
require_once __DIR__.'/import_common.php';
use SiscoEdu\PlanImport\ImportEnvironmentPreflight;
requerirMetodo(['GET']);
$user=usuarioActual(['SuperAdmin','Administracion','Coordinador','Profesor']);
try {
    $db=(new Database())->getConnection();
    $config=require __DIR__.'/../../config/plan_import.php';
    $data=(new ImportEnvironmentPreflight($db,$user,$config))->check();
    header('Cache-Control: no-store');
    $payload=['success'=>true,'data'=>$data];
    if (!$data['ready']) $payload['message']='El servidor no esta listo para analizar planes. Contacte a la administracion.';
    responderJson($payload, $data['ready']?200:503);
} catch (Throwable $error) { importError($error); }

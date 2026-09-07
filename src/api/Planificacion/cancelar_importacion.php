<?php
declare(strict_types=1);
require_once __DIR__.'/import_common.php';
use SiscoEdu\PlanImport\{PlanImportStaging,PlanImportException};
requerirMetodo(['DELETE']);
$user=usuarioActual(['SuperAdmin','Administracion','Coordinador','Profesor']);
try {
    importCsrfCheck();$config=require __DIR__.'/../../config/plan_import.php';$data=importJson($config);
    if(!is_string($data['token']??null))throw new PlanImportException('Token inválido.','INVALID_TOKEN');
    (new PlanImportStaging($config))->delete($data['token'],$user['id_usuario']);
    responderJson(['success'=>true]);
}catch(Throwable $e){importError($e);}

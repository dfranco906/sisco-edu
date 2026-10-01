<?php
declare(strict_types=1);
require_once __DIR__.'/import_common.php';
use SiscoEdu\PlanImport\{PlanImportStaging,PlanImportException,PlanImportacionService,CanonicalPlan,CatalogMatcher,ProgrammingValidator,ImportReadiness,PlanImportPersistence};
requerirMetodo(['POST']);
$user=usuarioActual(['SuperAdmin','Administracion','Coordinador','Profesor']);
try{
    importCsrfCheck();$config=require __DIR__.'/../../config/plan_import.php';$data=importJson($config);
    if(array_keys($data)!==['token']||!is_string($data['token']))throw new PlanImportException('Solicitud de confirmación inválida.','INVALID_CONFIRMATION');
    $db=(new Database())->getConnection();$service=new PlanImportacionService($db,$user,$config);
    $result=(new PlanImportStaging($config))->confirm($data['token'],$user['id_usuario'],function(array &$state,string $pdf)use($db,$user,$service):int{
        asegurarAccesoAsignacion($db,$user,$state['assignment'],true);
        if(!is_file($pdf)||!hash_equals($state['sha256'],hash_file('sha256',$pdf)))throw new PlanImportException('El PDF temporal no coincide con el staging.','STAGING_PDF_MISMATCH',6,409);
        CanonicalPlan::assertValid($state['plan']);
        $catalogs=$service->catalogs();$suggestions=(new CatalogMatcher())->suggestions($state['plan'],$catalogs);
        $preview=$service->previewData($state);if(!$preview['readiness']['can_confirm']){$issue=array_values(array_filter($preview['readiness']['issues'],static fn(array $i):bool=>$i['blocking']))[0];throw new PlanImportException($issue['message'],$issue['code'],6,409);}
        (new CatalogMatcher())->validate($state['decisions'],$suggestions,$catalogs,true);(new ProgrammingValidator())->validate($state['programming'],$state['plan'],$state['year']);
        $db->beginTransaction();try{$id=(new PlanImportPersistence($db))->persist($state['plan'],$state['assignment'],$state['year'],$user['id_usuario'],$state['decisions'],$state['programming']);$db->commit();return $id;}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    });
    responderJson(['success'=>true,'data'=>$result],$result['created']?201:200);
}catch(Throwable $e){importError($e);}

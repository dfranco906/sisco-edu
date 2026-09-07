<?php
declare(strict_types=1);
require_once __DIR__.'/import_common.php';
use SiscoEdu\PlanImport\{PlanImportStaging, PlanImportacionService, PreviewValidator, PlanImportException, CatalogMatcher};
requerirMetodo(['GET','PUT']);
$user = usuarioActual(['SuperAdmin','Administracion','Coordinador','Profesor']);
try {
    $config = require __DIR__.'/../../config/plan_import.php';
    $data = $_SERVER['REQUEST_METHOD']==='PUT' ? importJson($config) : $_GET;
    if ($_SERVER['REQUEST_METHOD']==='PUT') importCsrfCheck();
    if (!is_string($data['token'] ?? null)) throw new PlanImportException('Token inválido.', 'INVALID_TOKEN');
    $db = (new Database())->getConnection();
    $service = new PlanImportacionService($db,$user,$config);
    $result = (new PlanImportStaging($config))->withToken($data['token'],$user['id_usuario'],function(array &$state) use ($data,$db,$user,$service): array {
        asegurarAccesoAsignacion($db,$user,$state['assignment'],true);
        if ($_SERVER['REQUEST_METHOD']==='PUT') {
            if (!is_array($data['plan'] ?? null) || array_diff(array_keys($data),['token','plan','decisions'])) throw new PlanImportException('Payload de preview inválido.', 'INVALID_PREVIEW');
            $state['plan'] = (new PreviewValidator())->validate($data['plan'],$state);
            $catalogs=$service->catalogs();
            $matcher=new CatalogMatcher();
            $suggestions=$matcher->suggestions($state['plan'],$catalogs);
            $state['decisions']=$matcher->validate($data['decisions'] ?? $state['decisions'],$suggestions,$catalogs);
        }
        return $service->previewData($state);
    },$_SERVER['REQUEST_METHOD']==='PUT');
    responderJson(['success'=>true,'data'=>$result]);
} catch (Throwable $e) { importError($e); }

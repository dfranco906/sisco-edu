<?php
declare(strict_types=1);
require_once __DIR__.'/../support/TestEnvironment.php';
require_once dirname(__DIR__,3).'/src/api/Planificacion/import_common.php';
use SiscoEdu\PlanImport\ImportEnvironmentPreflight;
$env=new PlanImportTestEnvironment();
try {
    $result=$env->request('preflight_importacion.php');
    if($result['status']!==200||($result['json']['data']['ready']??false)!==true)throw new RuntimeException('Preflight configurado no listo');
    foreach(['usuario','asignaciones','schema','staging','pdftotext','limites'] as $check)if($result['json']['data']['checks'][$check]['status']!=='PASS')throw new RuntimeException('Fallo preflight '.$check);
    $json=json_encode($result['json']);
    foreach(['binary','storage_path','document_root','SISCO_PDFTOTEXT_PATH','PHPSESSID',$env->directory] as $private)if(str_contains($json,$private))throw new RuntimeException('Preflight expone informacion privada');
    if($env->request('preflight_importacion.php','GET',null,true,false)['status']!==401)throw new RuntimeException('Preflight sin sesion permitido');
    if($env->request('preflight_importacion.php','POST')['status']!==405)throw new RuntimeException('Preflight permite POST');
    $env->db->exec('UPDATE asignacion_docente SET activo=0');
    $empty=$env->request('preflight_importacion.php');
    if($empty['status']!==503||$empty['json']['data']['checks']['asignaciones']['status']!=='FAIL')throw new RuntimeException('Asignaciones vacias no bloqueadas');
    $env->db->exec('UPDATE asignacion_docente SET activo=1');
    $config=require dirname(__DIR__,3).'/src/config/plan_import.php';
    $config['document_root']=$env->directory;
    $config['storage_path']=$env->directory.'-storage';
    $config['pdftotext_binary']=$env->directory.'/missing-extractor.exe';
    $missing=(new ImportEnvironmentPreflight($env->db,['id_usuario'=>$env->user,'rol'=>'Profesor'],$config))->check();
    if($missing['ready']||$missing['checks']['pdftotext']['error_type']!=='EXTRACTOR_NOT_FOUND')throw new RuntimeException('Extractor ausente aceptado');
    $config['limits']['max_file_bytes']=-1;
    $badLimit=(new ImportEnvironmentPreflight($env->db,['id_usuario'=>$env->user,'rol'=>'Profesor'],$config))->check();
    if($badLimit['checks']['limites']['status']!=='FAIL')throw new RuntimeException('Limite negativo aceptado');
    $env->db->exec('ALTER TABLE plan_temas DROP COLUMN codigo');
    $schema=$env->request('preflight_importacion.php');
    if($schema['status']!==503||$schema['json']['data']['checks']['schema']['error_type']!=='SCHEMA_NOT_READY')throw new RuntimeException('Schema incompleto aceptado');
    $config['storage_path']=$env->directory.'/public/staging';
    $unsafe=(new ImportEnvironmentPreflight($env->db,['id_usuario'=>$env->user,'rol'=>'Profesor'],$config))->check();
    if($unsafe['checks']['staging']['status']!=='FAIL')throw new RuntimeException('Staging publico aceptado');
    if((int)$env->db->query('SELECT COUNT(*) FROM planes_anuales')->fetchColumn()!==0)throw new RuntimeException('Preflight escribio planes');
    echo "OK | ApiPreflightTest (sesion, schema, asignaciones, limites, extractor, almacenamiento privado; BD temporal)\n";
} finally { $env->close(); }

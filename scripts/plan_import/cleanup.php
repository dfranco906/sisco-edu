<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once dirname(__DIR__,2).'/src/classes/PlanImport/bootstrap.php';
use SiscoEdu\PlanImport\PlanImportStaging;
try {
    $args=array_slice($argv,1);
    if(array_diff($args,['--dry-run','--delete']))throw new RuntimeException('Uso: php cleanup.php [--dry-run | --delete]');
    $dryRun=!in_array('--delete',$args,true)||in_array('--dry-run',$args,true);
    $config=require dirname(__DIR__,2).'/src/config/plan_import.php';
    echo json_encode(['dry_run'=>$dryRun,'expired'=>(new PlanImportStaging($config))->cleanup($dryRun)],JSON_THROW_ON_ERROR).PHP_EOL;
}catch(Throwable $e){fwrite(STDERR,'No se pudo limpiar el staging: '.$e->getMessage().PHP_EOL);exit(1);}

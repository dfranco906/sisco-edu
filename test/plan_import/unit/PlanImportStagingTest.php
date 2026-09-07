<?php
declare(strict_types=1);
require_once dirname(__DIR__,3).'/src/classes/PlanImport/bootstrap.php';
use SiscoEdu\PlanImport\{PlanImportStaging, PlanImportException};
$config = require dirname(__DIR__,3).'/src/config/plan_import.php';
$config['storage_path'] = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sisco-stage-test-'.bin2hex(random_bytes(8));
$stage = new PlanImportStaging($config);
$pdf = glob(dirname(__DIR__,3).'/test/fixtures/planes/*.pdf')[0];
$tokens = [];
function rejectStage(callable $action, string $type): void {
    try { $action(); } catch (PlanImportException $e) { if ($e->errorType === $type) return; throw $e; }
    throw new RuntimeException('No rechazó '.$type);
}
try {
    $token = $tokens[] = $stage->create($pdf, 1, 2, 2026, '../../fuente.pdf');
    $stage->withToken($token, 1, function (array &$state): void { $state['plan']=['prueba'=>'á']; }, true);
    if ($stage->read($token,1)['plan'] !== ['prueba'=>'á']) throw new RuntimeException('Round trip');
    rejectStage(fn()=>$stage->read($token,2), 'TOKEN_FORBIDDEN');
    rejectStage(fn()=>$stage->delete($token,2), 'TOKEN_FORBIDDEN');
    rejectStage(fn()=>$stage->read('../'.$token,1), 'INVALID_TOKEN');
    try { $stage->withToken($token,1, function(array &$state): void { $state['plan']=null; throw new RuntimeException('interrumpido'); },true); } catch (RuntimeException) {}
    if ($stage->read($token,1)['plan'] !== ['prueba'=>'á']) throw new RuntimeException('Escritura parcial');
    $expired = $tokens[] = $stage->create($pdf,1,2,2026,'fuente.pdf');
    $stage->withToken($expired,1,function(array &$state): void { $state['expires']=time()-1; },true);
    rejectStage(fn()=>$stage->read($expired,1), 'TOKEN_EXPIRED');
    if ($stage->cleanup(true) !== [$expired]) throw new RuntimeException('Dry run incorrecto');
    if ($stage->cleanup(false) !== [$expired]) throw new RuntimeException('Limpieza incorrecta');
    $stage->read($token,1);
    $stage->withToken($token,1,function(array &$state): void { $state['state']='consumed'; },true);
    rejectStage(fn()=>$stage->read($token,1), 'TOKEN_CONSUMED');
    echo "OK | PlanImportStagingTest (ownership, traversal, atomicidad, expiración, limpieza, consumo)\n";
} finally {
    foreach ($tokens as $item) { try { $stage->delete($item,1); } catch (PlanImportException $e) { if ($e->errorType !== 'TOKEN_NOT_FOUND') throw $e; } }
    unlink($config['storage_path'].'/.lock'); rmdir($config['storage_path']);
}

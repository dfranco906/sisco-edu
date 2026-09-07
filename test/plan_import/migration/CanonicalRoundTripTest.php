<?php
declare(strict_types=1);
require_once dirname(__DIR__,3).'/src/classes/PlanImport/bootstrap.php';
require_once __DIR__.'/../support/TestEnvironment.php';
require_once __DIR__.'/../support/CanonicalPlanRoundTripRepository.php';

use SiscoEdu\PlanImport\PdfPlanImporter;

$env=new PlanImportTestEnvironment();
$failures=[];$passed=0;
function rtAssert(bool $ok,string $message):void {global $failures;if(!$ok)$failures[]=$message;}
function applyMigration(PDO $db,string $path):void {
    $sql=(string)file_get_contents($path);
    $active=preg_replace('/^\s*--.*$/m','',$sql)??'';
    foreach(array_filter(array_map('trim',explode(';',$active))) as $statement)$db->exec($statement);
}
try {
    $root=dirname(__DIR__,3);
    applyMigration($env->db,$root.'/database/migrations/20260903_planificacion_codigos_jerarquicos.sql');
    applyMigration($env->db,$root.'/database/migrations/20260907_plan_importacion_campos_sin_perdida.sql');
    // Re-ejecucion segura exclusivamente sobre la BD temporal.
    applyMigration($env->db,$root.'/database/migrations/20260903_planificacion_codigos_jerarquicos.sql');
    applyMigration($env->db,$root.'/database/migrations/20260907_plan_importacion_campos_sin_perdida.sql');

    $assignmentRow=$env->db->query('SELECT id_profesor,id_grado FROM asignacion_docente WHERE id_asignacion='.(int)$env->assignment)->fetch(PDO::FETCH_ASSOC);
    $assignments=[$env->assignment];
    for($i=1;$i<4;$i++){
        $matter=$env->insert('INSERT INTO materias (nombre,activo) VALUES (?,1)',['PDF ROUNDTRIP '.$i]);
        $assignments[]=$env->insert('INSERT INTO asignacion_docente (id_profesor,id_materia,id_grado,carga_horaria,anio_lectivo,activo) VALUES (?,?,?,4,2026,1)',
            [(int)$assignmentRow['id_profesor'],$matter,(int)$assignmentRow['id_grado']]);
    }
    if(trim((string)getenv('SISCO_PDFTOTEXT_PATH'))==='')
        putenv('SISCO_PDFTOTEXT_PATH=C:\\Program Files\\Git\\mingw64\\bin\\pdftotext.exe');
    $importer=PdfPlanImporter::fromConfig(require $root.'/src/config/plan_import.php');
    $repository=new CanonicalPlanRoundTripRepository($env->db);
    $fixtures=glob($root.'/test/fixtures/planes/*.pdf')?:[];
    sort($fixtures,SORT_STRING);
    rtAssert(count($fixtures)===4,'deben existir cuatro fixtures PDF');
    foreach($fixtures as $index=>$fixture){
        $before=$importer->import($fixture,true);
        $planId=$repository->persist($before,$assignments[$index],2026,$env->user);
        $after=$repository->reconstruct($planId);
        // JSON no distingue 8 de 8.0; la comparacion semantica tampoco debe hacerlo.
        if($before!=$after){
            $beforeJson=json_encode($before,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
            $afterJson=json_encode($after,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
            $failures[]=basename($fixture).' difiere tras MySQL'.PHP_EOL.'A='.substr((string)$beforeJson,0,1000).PHP_EOL.'B='.substr((string)$afterJson,0,1000);
        } else {$passed++;echo 'PASS ROUND-TRIP | '.basename($fixture).PHP_EOL;}
    }
    rtAssert((int)$env->db->query('SELECT COUNT(*) FROM planes_anuales')->fetchColumn()===4,'deben persistirse cuatro planes en la BD temporal');

    $probe=$importer->import($fixtures[0],true);
    $probe['unidades'][0]['capacidades'][0]['temas'][0]['indicadores'][0]['check']=true;
    try {$repository->persist($probe,$assignments[0],2026,$env->user);$failures[]='un check no nulo no debe persistirse como definicion';}
    catch(RuntimeException $e){rtAssert($e->getMessage()==='CHECK_IS_PROGRESS_NOT_PLAN_DEFINITION','codigo estable para Check operativo');}

    $matter=$env->insert('INSERT INTO materias (nombre,activo) VALUES (?,1)',['PDF ROLLBACK']);
    $rollbackAssignment=$env->insert('INSERT INTO asignacion_docente (id_profesor,id_materia,id_grado,carga_horaria,anio_lectivo,activo) VALUES (?,?,?,4,2026,1)',
        [(int)$assignmentRow['id_profesor'],$matter,(int)$assignmentRow['id_grado']]);
    $tables=['planes_anuales','plan_unidades','plan_capacidades','plan_temas','plan_indicadores',
        'procedimientos_evaluativos','instrumentos_evaluativos','plan_tema_procedimientos','plan_tema_instrumentos'];
    $baseline=[];foreach($tables as $table)$baseline[$table]=(int)$env->db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    try {$repository->persist($importer->import($fixtures[1],true),$rollbackAssignment,2026,$env->user,'catalog');$failures[]='fallo inyectado debe abortar';}
    catch(RuntimeException $e){rtAssert($e->getMessage()==='INJECTED_SQL_ROLLBACK_TEST','fallo inyectado controlado');}
    foreach($tables as $table)rtAssert((int)$env->db->query("SELECT COUNT(*) FROM $table")->fetchColumn()===$baseline[$table],
        'rollback debe restaurar conteo de '.$table);
} finally {$env->close();}

if($failures){fwrite(STDERR,"FALLAS ROUND-TRIP ($passed/4):\n- ".implode("\n- ",$failures)."\n");exit(1);}
echo "OK | CanonicalRoundTripTest | 4/4 PDF -> JSON A -> MySQL temporal -> JSON B\n";

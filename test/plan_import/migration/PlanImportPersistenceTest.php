<?php
declare(strict_types=1);
require_once dirname(__DIR__,3).'/src/classes/PlanImport/bootstrap.php';
require_once __DIR__.'/../support/TestEnvironment.php';
require_once __DIR__.'/../support/CanonicalPlanRoundTripRepository.php';
use SiscoEdu\PlanImport\{PdfPlanImporter,PlanImportPersistence,CatalogMatcher};
$env=new PlanImportTestEnvironment();
try{
    $root=dirname(__DIR__,3);if(trim((string)getenv('SISCO_PDFTOTEXT_PATH'))==='')putenv('SISCO_PDFTOTEXT_PATH=C:\\Program Files\\Git\\mingw64\\bin\\pdftotext.exe');
    $pdf=glob($root.'/test/fixtures/planes/*tercer curso*Administracion financiera.pdf')[0];$plan=PdfPlanImporter::fromConfig(require $root.'/src/config/plan_import.php')->import($pdf,true);
    $catalogs=['procedimientos'=>[],'instrumentos'=>[]];$suggestions=(new CatalogMatcher())->suggestions($plan,$catalogs);$decisions=array_map(static fn(array $s):array=>['kind'=>$s['kind'],'text'=>$s['text'],'action'=>'create','id'=>null],$suggestions);
    $programming=[['unidad_orden'=>1,'capacidad_orden'=>1,'tema_orden'=>1,'fecha_inicio'=>'2026-03-01','fecha_fin'=>'2026-03-31','horas_catedra_planificadas'=>4,'observaciones'=>'ConfirmaciÃ³n temporal']];
    $tables=['planes_anuales','plan_unidades','plan_capacidades','plan_temas','plan_indicadores','procedimientos_evaluativos','instrumentos_evaluativos','plan_tema_procedimientos','plan_tema_instrumentos','plan_tema_programacion'];$baseline=[];foreach($tables as $table)$baseline[$table]=(int)$env->db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    $env->db->beginTransaction();try{(new PlanImportPersistence($env->db,static function(string $point):void{if($point==='catalog')throw new RuntimeException('INJECTED');}))->persist($plan,$env->assignment,2026,$env->user,$decisions,$programming);throw new RuntimeException('No se inyectÃ³ fallo');}catch(RuntimeException $e){$env->db->rollBack();if($e->getMessage()!=='INJECTED')throw $e;}
    foreach($tables as $table)if((int)$env->db->query("SELECT COUNT(*) FROM $table")->fetchColumn()!==$baseline[$table])throw new RuntimeException('Rollback incompleto: '.$table);
    $env->db->beginTransaction();$id=(new PlanImportPersistence($env->db))->persist($plan,$env->assignment,2026,$env->user,$decisions,$programming);$env->db->commit();
    $head=$env->db->query('SELECT estado,importacion_origen_json FROM planes_anuales WHERE id_plan='.$id)->fetch(PDO::FETCH_ASSOC);if($head['estado']!=='BORRADOR'||!str_contains($head['importacion_origen_json'],$plan['source']['sha256']))throw new RuntimeException('Estado/origen incorrecto');
    $rebuilt=(new CanonicalPlanRoundTripRepository($env->db))->reconstruct($id);if($rebuilt!=$plan)throw new RuntimeException('Mapping productivo perdiÃ³ contenido');
    if((int)$env->db->query('SELECT COUNT(*) FROM plan_tema_programacion')->fetchColumn()!==1)throw new RuntimeException('ProgramaciÃ³n confirmada ausente');
    $bridge=$env->db->query('SELECT COUNT(*) FROM plan_tema_procedimientos WHERE texto_fuente IS NOT NULL AND orden>0')->fetchColumn();if((int)$bridge===0)throw new RuntimeException('Texto fuente/orden ausentes');
    echo "OK | PlanImportPersistenceTest (BORRADOR, mapping sin pÃ©rdida, catÃ¡logos/puentes/programaciÃ³n, transacciÃ³n externa y rollback)\n";
}finally{$env->close();}

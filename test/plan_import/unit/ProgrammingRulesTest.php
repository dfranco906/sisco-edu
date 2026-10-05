<?php
declare(strict_types=1);
require_once dirname(__DIR__,3).'/src/classes/PlanProgrammingRules.php';
require_once dirname(__DIR__,3).'/src/classes/PlanImport/bootstrap.php';
use SiscoEdu\PlanImport\{ProgrammingValidator,PlanImportException};
$checks=0;
$plan=['unidades'=>[['orden'=>1,'capacidades'=>[['orden'=>1,'temas'=>[['orden'=>1],['orden'=>2]]]]]]];
$row=fn(int $topic,string $start,string $end):array=>['unidad_orden'=>1,'capacidad_orden'=>1,'tema_orden'=>$topic,'fecha_inicio'=>$start,'fecha_fin'=>$end,'horas_catedra_planificadas'=>null,'observaciones'=>null];
foreach([
    [[$row(1,'2026-10-01','2026-10-10'),$row(2,'2026-10-05','2026-10-15')],false],
    [[$row(1,'2026-10-01','2026-10-10'),$row(2,'2026-10-10','2026-10-15')],false],
    [[$row(1,'2026-10-01','2026-10-10'),$row(2,'2026-10-11','2026-10-15')],true],
    [[$row(1,'2026-10-01','2026-10-10'),$row(1,'2026-10-05','2026-10-15')],true],
    [[$row(1,'2026-10-01','2026-10-10'),$row(1,'2026-10-01','2026-10-10')],false],
    [[$row(1,'','')],false], [[$row(1,'2026-02-30','2026-03-01')],false],
    [[$row(1,'2026-10-10','2026-10-01')],false], [[$row(1,'2025-10-01','2026-10-01')],false],
    [[$row(3,'2026-10-01','2026-10-01')],false],
] as [$rows,$expected]){
    $domainRows=array_map(fn($r)=>['topic_key'=>'1:1:'.$r['tema_orden']]+$r,$rows);
    $result=PlanProgrammingRules::inspect($domainRows,['1:1:1','1:1:2'],2026);
    if(!$result['errores']!==$expected)throw new RuntimeException('Dominio inconsistente');
    $accepted=true;try{(new ProgrammingValidator())->validate($rows,$plan,2026);}catch(PlanImportException $e){if($e->errorType!=='INVALID_PROGRAMMING')throw $e;$accepted=false;}
    if($accepted!==$expected)throw new RuntimeException('Importador inconsistente');$checks+=2;
}
echo "OK | ProgrammingRulesTest | $checks checks (fechas, año, pertenencia, duplicados, solapamientos inclusivos y paridad importador)\n";

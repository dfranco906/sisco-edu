<?php
declare(strict_types=1);
require_once dirname(__DIR__,3).'/src/classes/PlanImport/bootstrap.php';
use SiscoEdu\PlanImport\{CanonicalPlan,CatalogMatcher,PlanImportException};
$plan=CanonicalPlan::emptyForTest();$u=CanonicalPlan::unit(1,null,'Unidad');$c=CanonicalPlan::capacity(1,'Capacidad');$t=CanonicalPlan::topic(1,null,'Tema');
$t['procedimientos_evaluativos']=['  Observación  ','Desconocido','Ambiguo'];$c['temas'][]=$t;$u['capacidades'][]=$c;$plan['unidades'][]=$u;
$catalogs=['procedimientos'=>[['id'=>1,'nombre'=>'OBSERVACION'],['id'=>2,'nombre'=>'Ambiguo'],['id'=>3,'nombre'=>'AMBIGUO']],'instrumentos'=>[]];
$matcher=new CatalogMatcher();$suggestions=$matcher->suggestions($plan,$catalogs);
if(array_column($suggestions,'suggested_id')!==[1,null,null]||!$suggestions[2]['ambiguous'])throw new RuntimeException('Matches incorrectos');
$decisions=[['kind'=>'procedimientos','text'=>'  Observación  ','action'=>'existing','id'=>1],['kind'=>'procedimientos','text'=>'Desconocido','action'=>'create'],['kind'=>'procedimientos','text'=>'Ambiguo','action'=>'exclude']];
$matcher->validate($decisions,$suggestions,$catalogs,true);
try{$matcher->validate([],$suggestions,$catalogs,true);throw new RuntimeException('Aceptó pendientes');}catch(PlanImportException $e){if($e->errorType!=='CATALOG_DECISION_REQUIRED')throw$e;}
echo "OK | CatalogMatcherTest (acentos, espacios, exacto, desconocido, ambiguo, decisiones explícitas)\n";

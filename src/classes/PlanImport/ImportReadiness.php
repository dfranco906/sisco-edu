<?php
declare(strict_types=1);
namespace SiscoEdu\PlanImport;

final class ImportReadiness
{
    private const BLOCKING_WARNINGS=['UNKNOWN_FORMAT','PDF_SCAN_NOT_SUPPORTED','AMBIGUOUS_COLUMN','ORPHAN_TOPIC','ORPHAN_INDICATOR','MISSING_UNIT','MISSING_CAPACITY','MISSING_TOPIC','MISSING_INDICATOR','INVALID_HOURS','INVALID_YEAR','DUPLICATE_CODE','INCOHERENT_TOPIC_CODE','INCOHERENT_INDICATOR_CODE'];
    public function evaluate(array $plan,array $suggestions,array $decisions,?int $existingPlan): array
    {
        $issues=[];
        foreach($plan['warnings'] as $warning)$issues[]=['code'=>$warning['type'],'message'=>$warning['message'],'blocking'=>in_array($warning['type'],self::BLOCKING_WARNINGS,true)];
        if($existingPlan)$issues[]=['code'=>'PLAN_ALREADY_EXISTS','message'=>'Ya existe un plan para esta asignación y año.','blocking'=>true];
        foreach($plan['unidades'] as $u)foreach($u['capacidades'] as $c)foreach($c['temas'] as $t)foreach($t['indicadores'] as $i)if($i['check']!==null)$issues[]=['code'=>'CHECK_IS_PROGRESS_NOT_PLAN_DEFINITION','message'=>'CHECK representa avance operativo y no puede importarse como definición del plan.','blocking'=>true];
        $decided=[];foreach($decisions as $d)$decided[$d['kind'].':'.$d['text']]=true;
        foreach($suggestions as $suggestion)if(!isset($decided[$suggestion['kind'].':'.$suggestion['text']]))$issues[]=['code'=>'CATALOG_DECISION_REQUIRED','message'=>'Debe decidir cómo tratar: '.$suggestion['text'],'blocking'=>true];
        return ['can_confirm'=>count(array_filter($issues,static fn(array $i):bool=>$i['blocking']))===0,'issues'=>$issues];
    }
}

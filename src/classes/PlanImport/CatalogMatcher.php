<?php
declare(strict_types=1);
namespace SiscoEdu\PlanImport;

final class CatalogMatcher
{
    public function suggestions(array $plan, array $catalogs): array
    {
        $result=[];
        foreach ($plan['unidades'] as $u) foreach ($u['capacidades'] as $c) foreach ($c['temas'] as $t) {
            foreach (['procedimientos','instrumentos'] as $kind) foreach ($t[$kind.'_evaluativos'] as $text) {
                $key=$kind.':'.$text;
                if(isset($result[$key]))continue;
                $matches=array_values(array_filter($catalogs[$kind],static fn(array $entry): bool=>Text::key($entry['nombre'])===Text::key($text)));
                $result[$key]=['kind'=>$kind,'text'=>$text,'suggested_id'=>count($matches)===1?(int)$matches[0]['id']:null,'ambiguous'=>count($matches)>1];
            }
        }
        return array_values($result);
    }
    public function validate(array $decisions,array $suggestions,array $catalogs,bool $complete=false): array
    {
        if(!array_is_list($decisions))throw new PlanImportException('Decisiones inválidas.','INVALID_CATALOG_DECISION');
        $sources=[];foreach($suggestions as $s)$sources[$s['kind'].':'.$s['text']]=true;
        $seen=[];
        foreach($decisions as $d) {
            if(!is_array($d)||array_diff(array_keys($d),['kind','text','action','id'])||!is_string($d['kind']??null)||!is_string($d['text']??null))throw new PlanImportException('Decisión de catálogo inválida.','INVALID_CATALOG_DECISION');
            $key=$d['kind'].':'.$d['text'];
            if(!isset($sources[$key])||isset($seen[$key])||!in_array($d['action']??null,['existing','create','exclude'],true))throw new PlanImportException('Decisión de catálogo inválida.','INVALID_CATALOG_DECISION');
            if($d['action']==='existing') {
                if(!is_int($d['id']??null)||!in_array($d['id'],array_map('intval',array_column($catalogs[$d['kind']],'id')),true))throw new PlanImportException('El catálogo seleccionado no está activo.','INVALID_CATALOG_DECISION');
            } elseif(($d['id']??null)!==null)throw new PlanImportException('Esta decisión no admite ID.','INVALID_CATALOG_DECISION');
            if($d['action']==='create'&&(trim($d['text'])===''||mb_strlen($d['text'])>150))throw new PlanImportException('El nombre del catálogo debe tener entre 1 y 150 caracteres.','INVALID_CATALOG_DECISION');
            $seen[$key]=true;
        }
        if($complete&&count($seen)!==count($sources))throw new PlanImportException('Resuelva todos los procedimientos e instrumentos antes de confirmar.','CATALOG_DECISION_REQUIRED');
        return $decisions;
    }
}

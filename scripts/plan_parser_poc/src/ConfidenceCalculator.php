<?php
declare(strict_types=1);

namespace SiscoEdu\PlanParserPoc;

final class ConfidenceCalculator
{
    /** @param array<string,mixed> $plan @return array<string,mixed> */
    public function calculate(array $plan): array
    {
        $format = (string) $plan['source']['format'];
        $metadataFields = $format === 'COMPETENCIA_CONTENIDO'
            ? ['institucion','materia','profesor','curso','anio','dias_clase']
            : ['institucion','materia','profesor','curso','turno','anio','competencia_general','competencia_especifica'];
        $metadataFound = array_values(array_filter($metadataFields, fn(string $field): bool => ($plan['metadata'][$field] ?? null) !== null && $plan['metadata'][$field] !== ''));
        $metadata = (int) round(100 * count($metadataFound) / count($metadataFields));
        if ($this->hasWarning($plan, 'SOURCE_METADATA_MISMATCH')) $metadata = max(0, $metadata - 15);

        $units = $plan['unidades'];
        $capacities = [];
        $topics = [];
        $indicators = [];
        foreach ($units as $unit) foreach ($unit['capacidades'] as $capacity) {
            $capacities[] = $capacity;
            foreach ($capacity['temas'] as $topic) {
                $topics[] = $topic;
                foreach ($topic['indicadores'] as $indicator) $indicators[] = $indicator;
            }
        }
        $noCodeWarnings = !array_filter($plan['warnings'], static fn(array $w): bool => in_array($w['type'], ['INCOHERENT_TOPIC_CODE','INCOHERENT_INDICATOR_CODE','DUPLICATE_CODE'], true));
        $structureCriteria = [
            ['criterion'=>'Existe al menos una unidad','weight'=>15,'passed'=>count($units)>0],
            ['criterion'=>'Todas las unidades tienen capacidad','weight'=>20,'passed'=>count($units)>0 && !array_filter($units, static fn(array $u): bool => !$u['capacidades'])],
            ['criterion'=>'Todas las capacidades tienen tema','weight'=>20,'passed'=>count($capacities)>0 && !array_filter($capacities, static fn(array $c): bool => !$c['temas'])],
            ['criterion'=>'Todos los temas tienen indicador','weight'=>20,'passed'=>count($topics)>0 && !array_filter($topics, static fn(array $t): bool => !$t['indicadores'])],
            ['criterion'=>'Órdenes secuenciales no vacíos','weight'=>5,'passed'=>$this->ordersAreSequential($plan)],
            ['criterion'=>'Códigos jerárquicos coherentes o no aplicables','weight'=>10,'passed'=>$noCodeWarnings],
            ['criterion'=>'Horas presentes cuando el formato las exige','weight'=>10,'passed'=>!$this->hasWarning($plan, 'MISSING_HOURS')],
        ];
        $structure = array_sum(array_map(static fn(array $x): int => $x['passed'] ? $x['weight'] : 0, $structureCriteria));

        $associationCriteria = [
            ['criterion'=>'Sin temas huérfanos','weight'=>40,'passed'=>!$this->hasWarning($plan, 'ORPHAN_TOPIC')],
            ['criterion'=>'Sin indicadores huérfanos','weight'=>40,'passed'=>!$this->hasWarning($plan, 'ORPHAN_INDICATOR')],
            ['criterion'=>'Sin columnas ambiguas','weight'=>10,'passed'=>!$this->hasWarning($plan, 'AMBIGUOUS_COLUMN')],
            ['criterion'=>'Sin reconstrucción conservadora de tabla','weight'=>10,'passed'=>!$this->hasWarning($plan, 'TABLE_STRUCTURE_WARNING')],
        ];
        $associations = array_sum(array_map(static fn(array $x): int => $x['passed'] ? $x['weight'] : 0, $associationCriteria));
        $global = (int) round($metadata * 0.30 + $structure * 0.40 + $associations * 0.30);

        return [
            'scale' => 'rule_completeness_0_100',
            'metadata' => $metadata,
            'estructura' => $structure,
            'asociaciones' => $associations,
            'global' => $global,
            'criteria' => [
                'metadata' => ['expected'=>$metadataFields, 'found'=>$metadataFound],
                'estructura' => $structureCriteria,
                'asociaciones' => $associationCriteria,
                'formula_global' => '30% metadata + 40% estructura + 30% asociaciones',
            ],
        ];
    }

    private function hasWarning(array $plan, string $type): bool
    {
        return (bool) array_filter($plan['warnings'], static fn(array $warning): bool => $warning['type'] === $type);
    }

    private function ordersAreSequential(array $plan): bool
    {
        foreach ($plan['unidades'] as $unitIndex => $unit) {
            if ($unit['orden'] !== $unitIndex + 1) return false;
            foreach ($unit['capacidades'] as $capacityIndex => $capacity) {
                if ($capacity['orden'] !== $capacityIndex + 1) return false;
                foreach ($capacity['temas'] as $topicIndex => $topic) {
                    if ($topic['orden'] !== $topicIndex + 1) return false;
                    foreach ($topic['indicadores'] as $indicatorIndex => $indicator) {
                        if ($indicator['orden'] !== $indicatorIndex + 1) return false;
                    }
                }
            }
        }
        return true;
    }
}

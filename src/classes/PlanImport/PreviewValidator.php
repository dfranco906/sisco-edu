<?php
declare(strict_types=1);
namespace SiscoEdu\PlanImport;

final class PreviewValidator
{
    public function validate(array $plan, array $state): array
    {
        if (($plan['source'] ?? null) !== $state['plan']['source']) throw new PlanImportException('No puede cambiar el origen del documento.', 'IMMUTABLE_SOURCE');
        $plan['warnings'] = array_values(array_filter($state['parser_warnings'], static fn(array $w): bool => !in_array($w['type'], ['MISSING_METADATA','MISSING_HOURS','INVALID_YEAR','INVALID_ORDER','INVALID_HOURS','MISSING_UNIT','MISSING_CAPACITY','MISSING_TOPIC','MISSING_INDICATOR','DUPLICATE_CODE','INCOHERENT_TOPIC_CODE','INCOHERENT_INDICATOR_CODE'],true)));
        $plan['confidence'] = $state['plan']['confidence'];
        if (isset($state['plan']['debug'])) $plan['debug'] = $state['plan']['debug'];
        else unset($plan['debug']);
        CanonicalPlan::assertValid($plan);
        $this->list($plan['unidades']);
        foreach ($plan['unidades'] as $u) {
            $this->code($u['codigo']); $this->list($u['capacidades']);
            foreach ($u['capacidades'] as $c) {
                $this->list($c['temas']);
                foreach ($c['temas'] as $t) {
                    $this->code($t['codigo']); $this->list($t['indicadores']);
                    $this->list($t['procedimientos_evaluativos']); $this->list($t['instrumentos_evaluativos']);
                    foreach ($t['indicadores'] as $indicator) $this->code($indicator['codigo']);
                }
            }
        }
        $plan = (new PlanValidator())->validate($plan);
        $blocked = ['INVALID_ORDER','MISSING_UNIT','MISSING_CAPACITY','MISSING_TOPIC','MISSING_INDICATOR','INCOHERENT_TOPIC_CODE','INCOHERENT_INDICATOR_CODE','DUPLICATE_CODE','INVALID_HOURS','INVALID_YEAR'];
        foreach ($plan['warnings'] as $warning) if (in_array($warning['type'],$blocked,true)) throw new PlanImportException($warning['message'], 'INVALID_PREVIEW_STRUCTURE');
        $plan['confidence'] = (new ConfidenceCalculator())->calculate($plan);
        if (isset($plan['debug'])) $plan['debug']['counts'] = PdfPlanImporter::counts($plan);
        return $plan;
    }
    private function list(array $items): void { if (!array_is_list($items)) throw new PlanImportException('La jerarquía debe usar listas ordenadas.', 'INVALID_PREVIEW_STRUCTURE'); }
    private function code(?string $code): void { if ($code!==null && mb_strlen($code)>32) throw new PlanImportException('El código no puede superar 32 caracteres.', 'INVALID_PREVIEW_STRUCTURE'); }
}

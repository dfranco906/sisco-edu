<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3).'/src/classes/PlanImport/PlanImportException.php';
require_once dirname(__DIR__, 3).'/src/classes/PlanImport/CanonicalPlan.php';

use SiscoEdu\PlanImport\CanonicalPlan;
use SiscoEdu\PlanImport\PlanImportException;

$failures = [];
function canonicalAssert(bool $condition, string $message): void { global $failures; if (!$condition) $failures[] = $message; }

$schemaPath = dirname(__DIR__, 3).'/src/schema/plan-import.schema.json';
$schema = json_decode((string) file_get_contents($schemaPath), true, 512, JSON_THROW_ON_ERROR);
canonicalAssert(($schema['properties']['schema_version']['const'] ?? null) === '1.0', 'schema JSON debe fijar versión 1.0');

$plan = CanonicalPlan::emptyForTest();
CanonicalPlan::assertValid($plan);
canonicalAssert(CanonicalPlan::validationErrors($plan) === [], 'la fábrica debe crear un plan válido');

$unit = CanonicalPlan::unit(1, 'I', 'Unidad de prueba');
$capacity = CanonicalPlan::capacity(1, 'Capacidad de prueba');
$topic = CanonicalPlan::topic(1, '1.1', 'Tema de prueba');
$topic['indicadores'][] = CanonicalPlan::indicator(1, '1.1.1', 'Indicador de prueba');
$capacity['temas'][] = $topic;
$unit['capacidades'][] = $capacity;
$plan['unidades'][] = $unit;
$plan['warnings'][] = CanonicalPlan::warning('TEST_WARNING', 'Warning de prueba.', 1, 2, 'texto');
CanonicalPlan::assertValid($plan);

$invalidCases = [];
$case = $plan; unset($case['metadata']['materia']); $invalidCases['clave faltante'] = $case;
$case = $plan; $case['unidades'][0]['orden'] = '1'; $invalidCases['tipo incorrecto'] = $case;
$case = $plan; $case['unidades'][0]['extra'] = true; $invalidCases['clave extra'] = $case;
$case = $plan; $case['source']['sha256'] = 'abc'; $invalidCases['hash inválido'] = $case;
$case = $plan; $case['unidades'][0]['capacidades'][0]['temas'][0]['indicadores'][0]['check'] = 'sí'; $invalidCases['check inválido'] = $case;

foreach ($invalidCases as $name => $invalid) {
    try {
        CanonicalPlan::assertValid($invalid);
        $failures[] = $name.' no fue rechazado';
    } catch (PlanImportException $exception) {
        canonicalAssert($exception->errorType === 'INVALID_CANONICAL_SCHEMA', $name.' devolvió código incorrecto');
    }
}

if ($failures) {
    fwrite(STDERR, "FALLAS CANONICAL PLAN:\n- ".implode("\n- ", $failures)."\n");
    exit(1);
}
echo "OK | CanonicalPlanTest\n";

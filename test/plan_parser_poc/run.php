<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2).'/scripts/plan_parser_poc/bootstrap.php';

$root = dirname(__DIR__, 2);
$fixtureDir = $root.'/test/fixtures/planes';
$expectedDir = __DIR__.'/expected';
$outputDir = $root.'/test/output/planes';
if (!is_dir($outputDir) && !mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
    throw new RuntimeException('No se pudo crear test/output/planes.');
}

$schema = json_decode((string) file_get_contents($root.'/scripts/plan_parser_poc/schema/plan-import-poc.schema.json'), true, 512, JSON_THROW_ON_ERROR);
if (($schema['properties']['schema_version']['const'] ?? null) !== '1.0-poc') throw new RuntimeException('Schema JSON POC inválido.');

$application = createPlanParserPoc();
$failures = [];
$results = [];

function checkPoc(bool $condition, string $file, string $message): void
{
    global $failures;
    if (!$condition) $failures[] = "{$file}: {$message}";
}

function samePoc(mixed $actual, mixed $expected, string $file, string $message): void
{
    checkPoc($actual == $expected, $file, $message.' | esperado='.json_encode($expected, JSON_UNESCAPED_UNICODE).' actual='.json_encode($actual, JSON_UNESCAPED_UNICODE));
}

/** @return array{topics:array<int,array<string,mixed>>,indicators:array<int,array<string,mixed>>} */
function flattenedPoc(array $plan): array
{
    $topics = [];
    $indicators = [];
    foreach ($plan['unidades'] as $unit) foreach ($unit['capacidades'] as $capacity) foreach ($capacity['temas'] as $topic) {
        $topics[] = $topic;
        foreach ($topic['indicadores'] as $indicator) $indicators[] = $indicator;
    }
    return compact('topics', 'indicators');
}

function canonicalShapePoc(array $plan, string $file): void
{
    $top = ['schema_version','source','metadata','unidades','warnings','confidence','debug'];
    samePoc(array_keys($plan), $top, $file, 'claves raíz canónicas');
    foreach ($plan['unidades'] as $unit) {
        samePoc(array_keys($unit), ['orden','codigo','nombre','descripcion','horas_catedra','tiempo_texto','proceso_texto','area_transversal','metodologia','medios_verificacion','capacidades'], $file, 'claves de unidad');
        foreach ($unit['capacidades'] as $capacity) {
            samePoc(array_keys($capacity), ['orden','descripcion','proceso_desarrollo','temas'], $file, 'claves de capacidad');
            foreach ($capacity['temas'] as $topic) {
                samePoc(array_keys($topic), ['orden','codigo','titulo','contenido','horas_catedra','tiempo_texto','fecha_texto','indicadores','procedimientos_evaluativos','instrumentos_evaluativos'], $file, 'claves de tema');
                foreach ($topic['indicadores'] as $indicator) samePoc(array_keys($indicator), ['orden','codigo','descripcion','check'], $file, 'claves de indicador');
            }
        }
    }
}

foreach (glob($expectedDir.'/*.expected.json') ?: [] as $expectedPath) {
    $expected = json_decode((string) file_get_contents($expectedPath), true, 512, JSON_THROW_ON_ERROR);
    $file = $expected['filename'];
    $fixture = $fixtureDir.'/'.$file;
    checkPoc(is_file($fixture), $file, 'fixture PDF ausente');
    if (!is_file($fixture)) continue;
    $plan = $application->parse($fixture, true);
    canonicalShapePoc($plan, $file);
    $counts = $application->counts($plan);
    $flat = flattenedPoc($plan);

    samePoc($plan['source']['sha256'], $expected['sha256'], $file, 'SHA-256');
    samePoc($plan['source']['format'], $expected['format'], $file, 'formato');
    samePoc($plan['source']['pages'], $expected['pages'], $file, 'páginas');
    foreach ($expected['metadata'] as $key => $value) samePoc($plan['metadata'][$key] ?? null, $value, $file, "metadata.{$key}");
    samePoc($counts, $expected['counts'], $file, 'conteos estructurales');
    $structureHash = hash('sha256', json_encode($plan['unidades'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    samePoc($structureHash, $expected['structure_sha256'], $file, 'SHA-256 de la jerarquía canónica completa');
    samePoc(array_column($plan['unidades'], 'codigo'), $expected['unit_codes'], $file, 'códigos de unidades');
    samePoc(array_column($plan['unidades'], 'nombre'), $expected['unit_names'], $file, 'nombres de unidades');
    if (isset($expected['unit_hours'])) samePoc(array_column($plan['unidades'], 'horas_catedra'), $expected['unit_hours'], $file, 'HC por unidad');
    if (isset($expected['unit_processes'])) samePoc(array_column($plan['unidades'], 'proceso_texto'), $expected['unit_processes'], $file, 'proceso por unidad');
    if (isset($expected['capacity_processes'])) {
        $processes = array_map(static fn(array $unit): mixed => $unit['capacidades'][0]['proceso_desarrollo'] ?? null, $plan['unidades']);
        samePoc($processes, $expected['capacity_processes'], $file, 'proceso de desarrollo por capacidad');
    }
    $topicCodes = array_values(array_filter(array_column($flat['topics'], 'codigo'), static fn(mixed $value): bool => $value !== null));
    samePoc($topicCodes, $expected['topic_codes'] ?? [], $file, 'códigos de temas');
    if (isset($expected['indicator_codes'])) samePoc(array_column($flat['indicators'], 'codigo'), $expected['indicator_codes'], $file, 'códigos de indicadores');

    if (isset($expected['indicator_counts_by_unit'])) {
        $actual = [];
        foreach ($plan['unidades'] as $unit) {
            $count = 0;
            foreach ($unit['capacidades'] as $capacity) foreach ($capacity['temas'] as $topic) $count += count($topic['indicadores']);
            $actual[] = $count;
        }
        samePoc($actual, $expected['indicator_counts_by_unit'], $file, 'indicadores por unidad');
    }
    if (isset($expected['indicator_counts_by_capacity'])) {
        $actual = [];
        foreach ($plan['unidades'] as $unit) {
            $unitCounts = [];
            foreach ($unit['capacidades'] as $capacity) {
                $count = 0;
                foreach ($capacity['temas'] as $topic) $count += count($topic['indicadores']);
                $unitCounts[] = $count;
            }
            $actual[] = $unitCounts;
        }
        samePoc($actual, $expected['indicator_counts_by_capacity'], $file, 'indicadores por capacidad');
    }
    if (isset($expected['capacity_names_by_unit'])) {
        $actual = array_map(static fn(array $unit): array => array_column($unit['capacidades'], 'descripcion'), $plan['unidades']);
        samePoc($actual, $expected['capacity_names_by_unit'], $file, 'capacidades por unidad');
    }
    foreach (['procedures_by_unit'=>'procedimientos_evaluativos','instruments_by_unit'=>'instrumentos_evaluativos'] as $expectedKey => $topicKey) {
        if (!isset($expected[$expectedKey])) continue;
        $actual = array_map(static fn(array $unit): array => $unit['capacidades'][0]['temas'][0][$topicKey] ?? [], $plan['unidades']);
        samePoc($actual, $expected[$expectedKey], $file, $expectedKey);
    }

    $warningTypes = array_values(array_unique(array_column($plan['warnings'], 'type')));
    sort($warningTypes);
    $expectedWarnings = $expected['warning_types'];
    sort($expectedWarnings);
    samePoc($warningTypes, $expectedWarnings, $file, 'tipos de warning');
    if (isset($expected['warning_count'])) samePoc(count($plan['warnings']), $expected['warning_count'], $file, 'cantidad de warnings');
    samePoc($plan['confidence']['global'], $expected['confidence_global'], $file, 'confianza global');
    checkPoc(!array_intersect($warningTypes, ['ORPHAN_TOPIC','ORPHAN_INDICATOR','INCOHERENT_TOPIC_CODE','INCOHERENT_INDICATOR_CODE']), $file, 'existe una asociación huérfana o incoherente');

    foreach ($flat['indicators'] as $indicator) {
        if ($indicator['codigo'] === null) continue;
        $parent = implode('.', array_slice(explode('.', $indicator['codigo']), 0, 2));
        checkPoc(in_array($parent, array_column($flat['topics'], 'codigo'), true), $file, "indicador {$indicator['codigo']} sin tema exacto");
    }
    if ($expected['format'] === 'EXCEL_AVANZADO') {
        checkPoc(count(array_filter($flat['indicators'], static fn(array $indicator): bool => $indicator['check'] !== null)) === 0, $file, 'la columna Check vacía no debe inventar valores');
    }

    $sample = $expected['samples'] ?? [];
    $sampleTopic = null;
    if (isset($sample['topic_code'])) {
        foreach ($flat['topics'] as $topic) if ($topic['codigo'] === $sample['topic_code']) $sampleTopic = $topic;
    } else $sampleTopic = $flat['topics'][0] ?? null;
    if (isset($sample['topic_title'])) samePoc($sampleTopic['titulo'] ?? null, $sample['topic_title'], $file, 'muestra de tema');
    if (isset($sample['indicator_code'])) {
        $sampleIndicator = null;
        foreach ($flat['indicators'] as $indicator) if ($indicator['codigo'] === $sample['indicator_code']) $sampleIndicator = $indicator;
        samePoc($sampleIndicator['descripcion'] ?? null, $sample['indicator_text'], $file, 'muestra de indicador codificado');
    } elseif (isset($sample['indicator_text'])) samePoc($sampleTopic['indicadores'][0]['descripcion'] ?? null, $sample['indicator_text'], $file, 'muestra de indicador');
    if (isset($sample['last_topic_title'])) samePoc($flat['topics'][array_key_last($flat['topics'])]['titulo'] ?? null, $sample['last_topic_title'], $file, 'último tema');
    if (isset($sample['last_indicator_text'])) samePoc($flat['indicators'][array_key_last($flat['indicators'])]['descripcion'] ?? null, $sample['last_indicator_text'], $file, 'último indicador');

    $outputName = preg_replace('/\.expected\.json$/', '.extracted.json', basename($expectedPath));
    file_put_contents($outputDir.'/'.$outputName, json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL, LOCK_EX);
    $results[] = [
        'pdf'=>$file, 'format'=>$plan['source']['format'], 'counts'=>$counts,
        'warnings'=>count($plan['warnings']), 'confidence'=>$plan['confidence']['global'],
    ];
}

echo "PDF | FORMATO | U | C | T | I | WARNINGS | CONFIANZA\n";
foreach ($results as $result) {
    $c = $result['counts'];
    echo "{$result['pdf']} | {$result['format']} | {$c['unidades']} | {$c['capacidades']} | {$c['temas']} | {$c['indicadores']} | {$result['warnings']} | {$result['confidence']}\n";
}
if ($failures) {
    fwrite(STDERR, "\nFALLAS (".count($failures)."):\n- ".implode("\n- ", $failures)."\n");
    exit(1);
}
echo "OK: ".count($results)."/4 PDFs coinciden con sus expectativas revisadas.\n";

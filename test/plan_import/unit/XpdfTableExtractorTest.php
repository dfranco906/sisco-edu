<?php
declare(strict_types=1);

$root = dirname(__DIR__, 3);
require_once $root.'/src/classes/PlanImport/Contracts/PdfTextExtractorInterface.php';
require_once $root.'/src/classes/PlanImport/PlanImportException.php';
require_once $root.'/src/classes/PlanImport/ProcessRunner.php';
require_once $root.'/src/classes/PlanImport/Pdf/XpdfTableExtractor.php';

use SiscoEdu\PlanImport\Pdf\XpdfTableExtractor;
use SiscoEdu\PlanImport\PlanImportException;
use SiscoEdu\PlanImport\ProcessRunner;

$config = require $root.'/src/config/plan_import.php';
$config['pdftotext_binary'] = 'C:\\Program Files\\Git\\mingw64\\bin\\pdftotext.exe';
$failures = [];
function extractorAssert(bool $condition, string $message): void { global $failures; if (!$condition) $failures[] = $message; }
function expectExtractorError(callable $operation, string $type, string $label): void {
    global $failures;
    try { $operation(); $failures[] = $label.' no fue rechazado'; }
    catch (PlanImportException $exception) { if ($exception->errorType !== $type) $failures[] = $label.' devolvió '.$exception->errorType.' en vez de '.$type; }
}

$extractor = new XpdfTableExtractor($config);
$fixtures = glob($root.'/test/fixtures/planes/*.pdf') ?: [];
extractorAssert(count($fixtures) === 4, 'deben existir cuatro fixtures');
foreach ($fixtures as $fixture) {
    $document = $extractor->extract($fixture);
    extractorAssert($document['pages'] >= 1, basename($fixture).' debe informar páginas');
    extractorAssert(strlen($document['text']) > 40, basename($fixture).' debe extraer texto');
    extractorAssert(count($document['lines']) > 0, basename($fixture).' debe devolver líneas/coordenadas');
    extractorAssert(($document['extractor']['version'] ?? null) === '4.00', basename($fixture).' debe informar Xpdf 4.00');
    extractorAssert(!isset($document['extractor']['binary']), 'no debe exponer la ruta interna del binario');
}

$temp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sisco-plan-import-extractor-'.bin2hex(random_bytes(5));
mkdir($temp, 0700, true);
try {
    $empty = $temp.DIRECTORY_SEPARATOR.'empty.pdf'; file_put_contents($empty, '');
    $fake = $temp.DIRECTORY_SEPARATOR.'fake.pdf'; file_put_contents($fake, 'esto no es un archivo PDF');
    $corrupt = $temp.DIRECTORY_SEPARATOR.'corrupt.pdf'; file_put_contents($corrupt, '%PDF-no-es-un-pdf');
    $text = $temp.DIRECTORY_SEPARATOR.'not-pdf.txt'; file_put_contents($text, 'texto');
    expectExtractorError(fn()=>$extractor->extract($empty), 'INVALID_FILE_SIZE', 'PDF vacío');
    expectExtractorError(fn()=>$extractor->extract($fake), 'INVALID_PDF_SIGNATURE', 'firma PDF falsa');
    expectExtractorError(fn()=>$extractor->extract($corrupt), 'PDF_EXTRACTION_FAILED', 'PDF corrupto');
    expectExtractorError(fn()=>$extractor->extract($text), 'INVALID_EXTENSION', 'extensión no PDF');
    $badConfig = $config; $badConfig['pdftotext_binary'] = $temp.DIRECTORY_SEPARATOR.'missing.exe';
    expectExtractorError(fn()=>(new XpdfTableExtractor($badConfig))->extract($fixtures[0]), 'EXTRACTOR_NOT_FOUND', 'binario inexistente');
    $pageConfig = $config; $pageConfig['limits']['max_pages'] = 1;
    $twoPages = array_values(array_filter($fixtures, static fn(string $file): bool => str_contains($file, 'Excel Avanzado')))[0];
    expectExtractorError(fn()=>(new XpdfTableExtractor($pageConfig))->extract($twoPages), 'PDF_PAGE_LIMIT', 'límite de páginas');

    $runner = new ProcessRunner();
    $powershell = 'C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe';
    expectExtractorError(fn()=>$runner->run([$powershell, '-NoProfile', '-Command', 'Start-Sleep -Milliseconds 300'], 0.02, 1024), 'PARSER_TIMEOUT', 'timeout');
    expectExtractorError(fn()=>$runner->run([PHP_BINARY, '-r', 'echo str_repeat("x", 4096);'], 2, 128), 'PARSER_OUTPUT_LIMIT', 'límite de salida');
} finally {
    foreach (glob($temp.DIRECTORY_SEPARATOR.'*') ?: [] as $file) unlink($file);
    rmdir($temp);
}

if ($failures) { fwrite(STDERR, "FALLAS XPDF EXTRACTOR:\n- ".implode("\n- ", $failures)."\n"); exit(1); }
echo "OK | XpdfTableExtractorTest (4/4 fixtures)\n";

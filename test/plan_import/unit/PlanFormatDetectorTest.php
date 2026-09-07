<?php
declare(strict_types=1);

$root = dirname(__DIR__, 3);
foreach ([
    '/src/classes/PlanImport/Contracts/PdfTextExtractorInterface.php',
    '/src/classes/PlanImport/Contracts/PlanFormatParserInterface.php',
    '/src/classes/PlanImport/PlanImportException.php', '/src/classes/PlanImport/ProcessRunner.php',
    '/src/classes/PlanImport/Text.php', '/src/classes/PlanImport/Pdf/XpdfTableExtractor.php',
    '/src/classes/PlanImport/Pdf/PlanFormatDetector.php', '/src/classes/PlanImport/Pdf/ParserRegistry.php',
] as $file) require_once $root.$file;

use SiscoEdu\PlanImport\Contracts\PlanFormatParserInterface;
use SiscoEdu\PlanImport\Pdf\ParserRegistry;
use SiscoEdu\PlanImport\Pdf\PlanFormatDetector;
use SiscoEdu\PlanImport\Pdf\XpdfTableExtractor;
use SiscoEdu\PlanImport\PlanImportException;

$config = require $root.'/src/config/plan_import.php';
$config['pdftotext_binary'] = 'C:\\Program Files\\Git\\mingw64\\bin\\pdftotext.exe';
$extractor = new XpdfTableExtractor($config);
$detector = new PlanFormatDetector();
$failures = [];
function detectorAssert(bool $condition, string $message): void { global $failures; if (!$condition) $failures[] = $message; }

$expected = [
    'Plan Anual - tercer curso - BTI - Administracion financiera.pdf'=>['ADMINISTRACION_FINANCIERA',105],
    'Plan Anual - segundo curso - BTI - Administracion financiera.pdf'=>['ADMINISTRACION_FINANCIERA',105],
    'Plan Anual - tercer curso - BCB - Excel Avanzado.pdf'=>['EXCEL_AVANZADO',121],
    'Plan Anual - segundo curso - BCB - Excel Avanzado.pdf'=>['COMPETENCIA_CONTENIDO',100],
];
foreach ($expected as $filename => [$format, $score]) {
    $document = $extractor->extract($root.'/test/fixtures/planes/'.$filename);
    $detection = $detector->detect($document['text']);
    detectorAssert($detection['format'] === $format, $filename.' formato esperado '.$format);
    detectorAssert($detection['winning_score'] === $score, $filename.' score esperado '.$score);
    detectorAssert(count($detection['scores']) === 3, $filename.' debe exponer evidencia de tres formatos');
}

$excelWithoutAccents = 'PROYECTO ANUAL - MICROSOFT EXCEL UNIDAD CAPACIDADES TEMAS INDICADORES CHECK PROCESO PARA EL DESARROLLO PROCEDIMIENTOS INSTRUMENTOS TEMA 1.1 1.1.1';
detectorAssert($detector->detect($excelWithoutAccents)['format'] === 'EXCEL_AVANZADO', 'normalización sin acentos');
detectorAssert($detector->detect('Unidad Capacidades Indicadores')['format'] === null, 'encabezado parcial debe ser UNKNOWN_FORMAT');
detectorAssert($detector->detect('')['error_type'] === 'UNKNOWN_FORMAT', 'documento vacío debe ser UNKNOWN_FORMAT');
$ambiguous = 'ADMINISTRACION FINANCIERA - PLAN ANUAL UNIDAD TEMATICA CAPACIDADES PROCEDIMIENTOS EVALUATIVOS INSTRUMENTOS EVALUATIVOS TIEMPO HORAS CATEDRA PROCESO COMPETENCIA GENERAL COMPETENCIA ESPECIFICA COMPETENCIA CAPACIDAD INDICADORES CONTENIDOS AREA TRANSVERSAL METODOLOGIA DE ENSENANZA MEDIOS DE VERIFICACION FECHA DIAS DE CLASE';
detectorAssert($detector->detect($ambiguous)['format'] === null, 'scores cercanos deben ser UNKNOWN_FORMAT');

$stub = new class implements PlanFormatParserInterface {
    public function format(): string { return 'TEST'; }
    public function parse(array $document): array { return $document; }
};
$registry = new ParserRegistry([$stub]);
detectorAssert($registry->get('TEST') === $stub, 'registry debe devolver parser por formato');
try { $registry->get('NO_EXISTE'); $failures[] = 'registry debe rechazar formato ausente'; }
catch (PlanImportException $exception) { detectorAssert($exception->errorType === 'FORMAT_PARSER_NOT_FOUND', 'código registry ausente'); }
try { new ParserRegistry([$stub, $stub]); $failures[] = 'registry debe rechazar duplicados'; }
catch (PlanImportException $exception) { detectorAssert($exception->errorType === 'DUPLICATE_FORMAT_PARSER', 'código registry duplicado'); }

if ($failures) { fwrite(STDERR, "FALLAS FORMAT DETECTOR:\n- ".implode("\n- ", $failures)."\n"); exit(1); }
echo "OK | PlanFormatDetectorTest (4/4 fixtures + UNKNOWN)\n";

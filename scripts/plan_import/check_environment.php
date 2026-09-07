<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

const PLAN_IMPORT_PREFLIGHT_OK = 0;
const PLAN_IMPORT_PREFLIGHT_CONFIG_ERROR = 2;
const PLAN_IMPORT_PREFLIGHT_BINARY_ERROR = 3;
const PLAN_IMPORT_PREFLIGHT_STORAGE_ERROR = 4;
const PLAN_IMPORT_PREFLIGHT_EXTRACT_ERROR = 5;

/** @return never */
function preflightFail(string $message, int $exitCode, array $details = []): never
{
    fwrite(STDERR, $message.PHP_EOL);
    echo json_encode([
        'ok' => false,
        'message' => $message,
        'details' => $details,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit($exitCode);
}

/** @return array{exit_code:int,stdout:string,stderr:string} */
function preflightRun(array $command): array
{
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open($command, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
    if (!is_resource($process)) {
        preflightFail('No se pudo iniciar el ejecutable configurado.', PLAN_IMPORT_PREFLIGHT_BINARY_ERROR);
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    return ['exit_code'=>$exitCode, 'stdout'=>(string) $stdout, 'stderr'=>(string) $stderr];
}

function preflightNormalizedPath(string $path): string
{
    $path = str_replace('\\', '/', $path);
    $path = rtrim($path, '/').'/';
    return PHP_OS_FAMILY === 'Windows' ? mb_strtolower($path, 'UTF-8') : $path;
}

$config = require dirname(__DIR__, 2).'/src/config/plan_import.php';
if (!is_array($config) || !isset($config['limits']) || !is_array($config['limits'])) {
    preflightFail('La configuración de importación no es válida.', PLAN_IMPORT_PREFLIGHT_CONFIG_ERROR);
}
if (!function_exists('proc_open')) {
    preflightFail('PHP no tiene disponible proc_open.', PLAN_IMPORT_PREFLIGHT_CONFIG_ERROR);
}

$binary = (string) ($config['pdftotext_binary'] ?? '');
if ($binary === '' || !is_file($binary) || !is_readable($binary)) {
    preflightFail('Configure SISCO_PDFTOTEXT_PATH con la ruta absoluta a Xpdf pdftotext.', PLAN_IMPORT_PREFLIGHT_BINARY_ERROR);
}
$binary = realpath($binary) ?: $binary;

$storage = (string) ($config['storage_path'] ?? '');
$documentRoot = realpath((string) ($config['document_root'] ?? ''));
if ($storage === '' || $documentRoot === false) {
    preflightFail('No se pudo resolver el almacenamiento privado o el document root.', PLAN_IMPORT_PREFLIGHT_STORAGE_ERROR);
}
if (!is_dir($storage) && !mkdir($storage, 0700, true) && !is_dir($storage)) {
    preflightFail('No se pudo crear el almacenamiento temporal privado.', PLAN_IMPORT_PREFLIGHT_STORAGE_ERROR);
}
$storageReal = realpath($storage);
if ($storageReal === false || !is_writable($storageReal)) {
    preflightFail('El almacenamiento temporal no permite escritura.', PLAN_IMPORT_PREFLIGHT_STORAGE_ERROR);
}
if (str_starts_with(preflightNormalizedPath($storageReal), preflightNormalizedPath($documentRoot))) {
    preflightFail('El almacenamiento temporal debe estar fuera de DOCUMENT_ROOT.', PLAN_IMPORT_PREFLIGHT_STORAGE_ERROR);
}

$versionResult = preflightRun([$binary, '-v']);
$versionText = trim($versionResult['stdout'].PHP_EOL.$versionResult['stderr']);
// Xpdf 4.00 devuelve 99 para las opciones informativas -v/-h; el contenido
// identificado es la señal fiable. Las extracciones reales sí deben devolver 0.
if (preg_match('/pdftotext\s+version\s+([0-9.]+)/i', $versionText, $versionMatch) !== 1) {
    preflightFail('El ejecutable configurado no responde como pdftotext.', PLAN_IMPORT_PREFLIGHT_BINARY_ERROR);
}
$helpResult = preflightRun([$binary, '-h']);
$helpText = $helpResult['stdout'].PHP_EOL.$helpResult['stderr'];
if (!str_contains($helpText, '-table')) {
    preflightFail('El pdftotext configurado no soporta la opción -table requerida.', PLAN_IMPORT_PREFLIGHT_BINARY_ERROR, ['version'=>$versionMatch[1]]);
}
if (!str_contains($versionText, 'Glyph & Cog')) {
    preflightFail('El ejecutable no pudo identificarse como la distribución Xpdf validada.', PLAN_IMPORT_PREFLIGHT_BINARY_ERROR, ['version'=>$versionMatch[1]]);
}

$sample = (string) ($config['preflight_sample_pdf'] ?? '');
if (!is_file($sample)) {
    preflightFail('No se encontró el PDF fixture usado por el preflight.', PLAN_IMPORT_PREFLIGHT_EXTRACT_ERROR);
}
$extractResult = preflightRun([$binary, '-enc', 'UTF-8', '-table', '--', $sample, '-']);
$printable = preg_replace('/[^\p{L}\p{N}]+/u', '', $extractResult['stdout']) ?? '';
if ($extractResult['exit_code'] !== 0 || mb_strlen($printable, 'UTF-8') < 40) {
    preflightFail('Xpdf no pudo extraer el fixture digital con -table.', PLAN_IMPORT_PREFLIGHT_EXTRACT_ERROR, ['stderr'=>trim($extractResult['stderr'])]);
}

echo json_encode([
    'ok' => true,
    'extractor' => 'Xpdf pdftotext',
    'version' => $versionMatch[1],
    'supports_table' => true,
    'binary' => $binary,
    'storage' => $storageReal,
    'limits' => $config['limits'],
    'sample_text_bytes' => strlen($extractResult['stdout']),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
exit(PLAN_IMPORT_PREFLIGHT_OK);

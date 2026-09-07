<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__, 2).'/src/classes/PlanImport/bootstrap.php';
use SiscoEdu\PlanImport\{PdfPlanImporter, PlanImportException};

$debug = false; $pretty = false; $output = null; $paths = [];
try {
    $args = array_slice($argv, 1);
    for ($i=0; $i<count($args); $i++) {
        $arg = $args[$i];
        if ($arg === '--debug') $debug = true;
        elseif ($arg === '--pretty') $pretty = true;
        elseif ($arg === '--output') $output = $args[++$i] ?? '';
        elseif (str_starts_with($arg, '--output=')) $output = substr($arg, 9);
        elseif (str_starts_with($arg, '--')) throw new PlanImportException('Opción desconocida.', 'CLI_USAGE', 64);
        else $paths[] = $arg;
    }
    if (count($paths) !== 1 || $output === '') throw new PlanImportException('Uso: php parse.php [--debug] [--pretty] [--output=archivo.json] archivo.pdf', 'CLI_USAGE', 64);
    $config = require dirname(__DIR__, 2).'/src/config/plan_import.php';
    $plan = PdfPlanImporter::fromConfig($config)->import($paths[0], $debug);
    $json = json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR | ($pretty ? JSON_PRETTY_PRINT : 0)).PHP_EOL;
    if ($output !== null) {
        // No sobrescribir documentos ni archivos existentes por un error de argumento.
        if (strtolower(pathinfo($output, PATHINFO_EXTENSION)) !== 'json' || is_link($output)) throw new PlanImportException('La salida debe ser un archivo .json nuevo.', 'CLI_OUTPUT_ERROR', 73);
        $handle = @fopen($output, 'xb');
        if (!$handle) throw new PlanImportException('No se pudo crear la salida; compruebe el directorio y que el archivo no exista.', 'CLI_OUTPUT_ERROR', 73);
        try { if (fwrite($handle, $json) !== strlen($json)) throw new PlanImportException('No se pudo completar la salida.', 'CLI_OUTPUT_ERROR', 73); }
        finally { fclose($handle); }
    }
    echo $json;
    if ($debug) fwrite(STDERR, json_encode(['format'=>$plan['source']['format'], 'counts'=>PdfPlanImporter::counts($plan), 'warnings'=>count($plan['warnings'])], JSON_UNESCAPED_UNICODE).PHP_EOL);
} catch (Throwable $e) {
    $type = $e instanceof PlanImportException ? $e->errorType : 'INTERNAL_ERROR';
    $message = $e instanceof PlanImportException ? $e->getMessage() : 'Error interno del importador.';
    echo json_encode(['error'=>['type'=>$type, 'message'=>$message]], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL;
    fwrite(STDERR, $type.': '.$message.PHP_EOL);
    exit($e instanceof PlanImportException ? $e->exitCode : 70);
}

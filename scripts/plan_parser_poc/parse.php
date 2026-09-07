<?php
declare(strict_types=1);

use SiscoEdu\PlanParserPoc\PlanParserException;

require_once __DIR__.'/bootstrap.php';

$debug = false;
$pretty = false;
$output = null;
$binary = null;
$positional = [];
foreach (array_slice($argv, 1) as $argument) {
    if ($argument === '--debug') $debug = true;
    elseif ($argument === '--pretty') $pretty = true;
    elseif (str_starts_with($argument, '--output=')) $output = substr($argument, 9);
    elseif (str_starts_with($argument, '--pdftotext=')) $binary = substr($argument, 13);
    elseif (str_starts_with($argument, '--')) {
        fwrite(STDERR, "Opción desconocida: {$argument}\n");
        exit(64);
    } else $positional[] = $argument;
}
if (count($positional) !== 1) {
    fwrite(STDERR, "Uso: php parse.php [--debug] [--pretty] [--output=archivo.json] [--pdftotext=ruta] archivo.pdf\n");
    exit(64);
}

try {
    $application = createPlanParserPoc($binary);
    $plan = $application->parse($positional[0], $debug);
    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;
    if ($pretty) $flags |= JSON_PRETTY_PRINT;
    $json = json_encode($plan, $flags).PHP_EOL;
    if ($output !== null) {
        $directory = dirname($output);
        if (!is_dir($directory) || !is_writable($directory)) throw new RuntimeException('El directorio de salida no existe o no permite escritura.');
        if (file_put_contents($output, $json, LOCK_EX) === false) throw new RuntimeException('No se pudo escribir el JSON de salida.');
    } else echo $json;
    if ($debug) {
        $counts = $plan['debug']['counts'];
        fwrite(STDERR, "Formato detectado: {$plan['source']['format']} (score {$plan['debug']['format_detection']['winning_score']})\n");
        fwrite(STDERR, "Páginas: {$plan['source']['pages']}\n");
        fwrite(STDERR, 'Columnas: '.json_encode($plan['debug']['columns'] ?? $plan['debug']['columns_by_page'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        fwrite(STDERR, "Unidades: {$counts['unidades']} | Capacidades: {$counts['capacidades']} | Temas: {$counts['temas']} | Indicadores: {$counts['indicadores']}\n");
        fwrite(STDERR, 'Warnings: '.count($plan['warnings'])."\n");
    }
    exit(0);
} catch (PlanParserException $exception) {
    $payload = [
        'schema_version'=>'1.0-poc', 'source'=>['filename'=>basename($positional[0])],
        'error'=>['type'=>$exception->warningType,'message'=>$exception->getMessage()],
        'warnings'=>[['type'=>$exception->warningType,'page'=>null,'line'=>null,'text'=>null,'message'=>$exception->getMessage()]],
    ];
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL;
    exit($exception->exitCode);
} catch (Throwable $exception) {
    $message = $debug ? $exception->getMessage() : 'Error interno de la POC.';
    echo json_encode(['error'=>['type'=>'INTERNAL_ERROR','message'=>$message]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit(70);
}

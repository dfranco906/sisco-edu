<?php
declare(strict_types=1);

$root = dirname(__DIR__, 3);
$php = PHP_BINARY;
$script = $root.'/scripts/plan_import/check_environment.php';
$validBinary = 'C:\\Program Files\\Git\\mingw64\\bin\\pdftotext.exe';
$failures = [];

/** @return array{exit:int,stdout:string,stderr:string} */
function runPreflightTest(string $php, string $script, array $environment): array
{
    $descriptors = [0=>['pipe','r'], 1=>['pipe','w'], 2=>['pipe','w']];
    $process = proc_open([$php, $script], $descriptors, $pipes, null, array_merge($_ENV, $environment), ['bypass_shell'=>true]);
    if (!is_resource($process)) throw new RuntimeException('No se pudo iniciar preflight.');
    fclose($pipes[0]);
    $stdout = (string) stream_get_contents($pipes[1]); fclose($pipes[1]);
    $stderr = (string) stream_get_contents($pipes[2]); fclose($pipes[2]);
    return ['exit'=>proc_close($process), 'stdout'=>$stdout, 'stderr'=>$stderr];
}

function preflightAssert(bool $condition, string $message): void
{
    global $failures;
    if (!$condition) $failures[] = $message;
}

$valid = runPreflightTest($php, $script, [
    'SISCO_PDFTOTEXT_PATH'=>$validBinary,
    'SISCO_PLAN_IMPORT_STORAGE'=>sys_get_temp_dir().DIRECTORY_SEPARATOR.'sisco-edu-plan-import-test',
]);
$validJson = json_decode($valid['stdout'], true);
preflightAssert($valid['exit'] === 0, 'la configuración válida debe terminar con exit 0');
preflightAssert(is_array($validJson) && ($validJson['ok'] ?? false) === true, 'la salida válida debe ser JSON ok');
preflightAssert(($validJson['supports_table'] ?? false) === true, 'debe confirmar soporte -table');

$invalid = runPreflightTest($php, $script, [
    'SISCO_PDFTOTEXT_PATH'=>$root.'/no-existe/pdftotext.exe',
    'SISCO_PLAN_IMPORT_STORAGE'=>sys_get_temp_dir().DIRECTORY_SEPARATOR.'sisco-edu-plan-import-test',
]);
$invalidJson = json_decode($invalid['stdout'], true);
preflightAssert($invalid['exit'] !== 0, 'la ruta inválida debe terminar con exit no cero');
preflightAssert(is_array($invalidJson) && ($invalidJson['ok'] ?? true) === false, 'la salida inválida debe ser JSON de error');
preflightAssert(str_contains($invalid['stderr'], 'SISCO_PDFTOTEXT_PATH'), 'el error debe indicar cómo configurar la ruta');

if ($failures) {
    fwrite(STDERR, "FALLAS PREFLIGHT:\n- ".implode("\n- ", $failures)."\n");
    exit(1);
}
echo "OK | EnvironmentPreflightTest\n";

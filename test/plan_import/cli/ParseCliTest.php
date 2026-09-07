<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3).'/src/classes/PlanImport/bootstrap.php';
use SiscoEdu\PlanImport\{ProcessRunner, CanonicalPlan};
$root = dirname(__DIR__, 3);
putenv('SISCO_PDFTOTEXT_PATH='.(getenv('SISCO_PDFTOTEXT_PATH') ?: 'C:\\Program Files\\Git\\mingw64\\bin\\pdftotext.exe'));
$runner = new ProcessRunner();
foreach (glob($root.'/test/fixtures/planes/*.pdf') as $pdf) {
    $result = $runner->run([PHP_BINARY, $root.'/scripts/plan_import/parse.php', '--debug', '--pretty', $pdf], 30, 5*1024*1024);
    if ($result['exit_code'] !== 0 || $result['stderr'] === '') throw new RuntimeException('CLI falló: '.$result['stderr']);
    CanonicalPlan::assertValid(json_decode($result['stdout'], true, 512, JSON_THROW_ON_ERROR));
}
$result = $runner->run([PHP_BINARY, $root.'/scripts/plan_import/parse.php', $root.'/missing.pdf'], 10, 65536);
if ($result['exit_code'] !== 2 || json_decode($result['stdout'], true, 512, JSON_THROW_ON_ERROR)['error']['type'] !== 'PDF_NOT_FOUND') throw new RuntimeException('Error CLI no controlado');
$output = sys_get_temp_dir().'/sisco-cli-'.bin2hex(random_bytes(8)).'.json';
try {
    $pdf = glob($root.'/test/fixtures/planes/*.pdf')[0];
    $result = $runner->run([PHP_BINARY, $root.'/scripts/plan_import/parse.php', '--output', $output, $pdf], 30, 5*1024*1024);
    if ($result['exit_code'] !== 0 || file_get_contents($output) !== $result['stdout']) throw new RuntimeException('Salida JSON difiere');
    $result = $runner->run([PHP_BINARY, $root.'/scripts/plan_import/parse.php', '--output', $output, $pdf], 30, 5*1024*1024);
    if ($result['exit_code'] !== 73) throw new RuntimeException('Sobrescribió salida existente');
} finally { if (is_file($output)) unlink($output); }
echo "OK | ParseCliTest (4 fixtures, debug, errores, output)\n";

<?php
declare(strict_types=1);

namespace SiscoEdu\PlanImport;

final class ProcessRunner
{
    /** @return array{exit_code:int,stdout:string,stderr:string,elapsed_ms:float} */
    public function run(array $command, float $timeoutSeconds, int $maxOutputBytes): array
    {
        $stdoutPath = tempnam(sys_get_temp_dir(), 'sisco-proc-out-');
        $stderrPath = tempnam(sys_get_temp_dir(), 'sisco-proc-err-');
        if ($stdoutPath === false || $stderrPath === false) {
            throw new PlanImportException('No se pudieron crear archivos temporales para el proceso.', 'EXTRACTOR_START_FAILED', 4, 500);
        }
        $descriptors = [0=>['pipe','r'], 1=>['file',$stdoutPath,'wb'], 2=>['file',$stderrPath,'wb']];
        $started = hrtime(true);
        $process = proc_open($command, $descriptors, $pipes, null, null, ['bypass_shell'=>true]);
        if (!is_resource($process)) {
            @unlink($stdoutPath);
            @unlink($stderrPath);
            throw new PlanImportException('No se pudo iniciar el extractor PDF.', 'EXTRACTOR_START_FAILED', 4, 500);
        }
        fclose($pipes[0]);
        $stdout = '';
        $stderr = '';
        $exitCode = null;
        try {
            while (true) {
                clearstatcache(true, $stdoutPath);
                clearstatcache(true, $stderrPath);
                $currentBytes = (int) (@filesize($stdoutPath) ?: 0) + (int) (@filesize($stderrPath) ?: 0);
                if ($currentBytes > $maxOutputBytes) {
                    proc_terminate($process);
                    throw new PlanImportException('La salida del extractor supera el límite permitido.', 'PARSER_OUTPUT_LIMIT', 7, 422);
                }
                $status = proc_get_status($process);
                if (!$status['running']) {
                    $exitCode = (int) $status['exitcode'];
                    break;
                }
                $elapsedSeconds = (hrtime(true) - $started) / 1_000_000_000;
                if ($elapsedSeconds > $timeoutSeconds) {
                    proc_terminate($process);
                    usleep(10000);
                    $status = proc_get_status($process);
                    if ($status['running']) proc_terminate($process, 9);
                    throw new PlanImportException('El extractor PDF excedió el tiempo máximo.', 'PARSER_TIMEOUT', 8, 408);
                }
                usleep(10000);
            }
        } finally {
            $closedCode = proc_close($process);
            if ($exitCode === null && $closedCode >= 0) $exitCode = $closedCode;
            // También limitar la lectura tras terminar: el proceso puede haber
            // escrito mucho entre dos sondeos o mientras se lo terminaba.
            $stdout = (string) @file_get_contents($stdoutPath, false, null, 0, $maxOutputBytes + 1);
            $stderr = (string) @file_get_contents($stderrPath, false, null, 0, $maxOutputBytes + 1);
            @unlink($stdoutPath);
            @unlink($stderrPath);
        }
        if (strlen($stdout) + strlen($stderr) > $maxOutputBytes) {
            throw new PlanImportException('La salida del extractor supera el límite permitido.', 'PARSER_OUTPUT_LIMIT', 7, 422);
        }
        return [
            'exit_code'=>$exitCode ?? -1,
            'stdout'=>$stdout,
            'stderr'=>$stderr,
            'elapsed_ms'=>round((hrtime(true) - $started) / 1_000_000, 2),
        ];
    }
}

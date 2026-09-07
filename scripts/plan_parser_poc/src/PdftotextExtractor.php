<?php
declare(strict_types=1);

namespace SiscoEdu\PlanParserPoc;

final class PdftotextExtractor implements PdfTextExtractorInterface
{
    private const MAX_BYTES = 20 * 1024 * 1024;

    public function __construct(private readonly ?string $configuredBinary = null)
    {
    }

    public function extract(string $pdfPath): array
    {
        $real = realpath($pdfPath);
        if ($real === false || !is_file($real)) {
            throw new PlanParserException('El archivo PDF no existe.', 'PDF_NOT_FOUND', 2);
        }
        if (strtolower(pathinfo($real, PATHINFO_EXTENSION)) !== 'pdf') {
            throw new PlanParserException('La extensión del archivo debe ser .pdf.', 'INVALID_EXTENSION', 2);
        }
        $size = filesize($real);
        if ($size === false || $size < 5 || $size > self::MAX_BYTES) {
            throw new PlanParserException('El PDF está vacío o supera el límite POC de 20 MB.', 'INVALID_FILE_SIZE', 2);
        }
        $handle = fopen($real, 'rb');
        $signature = $handle ? fread($handle, 5) : false;
        if (is_resource($handle)) fclose($handle);
        if ($signature !== '%PDF-') {
            throw new PlanParserException('La firma real del archivo no corresponde a un PDF.', 'INVALID_PDF_SIGNATURE', 2);
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($real);
        if (!in_array($mime, ['application/pdf', 'application/x-pdf'], true)) {
            throw new PlanParserException("MIME real no permitido: {$mime}.", 'INVALID_MIME', 2);
        }

        $binary = $this->locateBinary();
        $command = [$binary, '-enc', 'UTF-8', '-table', '--', $real, '-'];
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $started = hrtime(true);
        $process = proc_open($command, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
        if (!is_resource($process)) {
            throw new PlanParserException('No se pudo iniciar pdftotext.', 'EXTRACTOR_START_FAILED');
        }
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        $elapsedMs = round((hrtime(true) - $started) / 1_000_000, 2);
        if ($exit !== 0 || $stdout === false) {
            $detail = Text::clean((string) $stderr);
            throw new PlanParserException('Falló pdftotext'.($detail ? ": {$detail}" : '.'), 'PDF_EXTRACTION_FAILED');
        }

        $printable = preg_replace('/[^\p{L}\p{N}]+/u', '', $stdout) ?? '';
        if (mb_strlen($printable, 'UTF-8') < 40) {
            throw new PlanParserException(
                'Este PDF parece ser una imagen escaneada. Utilice un PDF digital o cargue el plan manualmente.',
                'PDF_SCAN_NOT_SUPPORTED',
                3
            );
        }
        $formFeeds = substr_count($stdout, "\f");
        $pages = $formFeeds + (str_ends_with($stdout, "\f") ? 0 : 1);

        return [
            'path' => $real,
            'filename' => basename($real),
            'sha256' => hash_file('sha256', $real),
            'pages' => max(1, $pages),
            'text' => $stdout,
            'extractor' => [
                'name' => 'pdftotext',
                'mode' => 'table',
                'binary' => $binary,
                'elapsed_ms' => $elapsedMs,
                'stderr' => Text::clean((string) $stderr) ?: null,
            ],
        ];
    }

    private function locateBinary(): string
    {
        $candidates = array_filter([
            $this->configuredBinary,
            getenv('SISCO_PDFTOTEXT_PATH') ?: null,
            'C:\\Program Files\\Git\\mingw64\\bin\\pdftotext.exe',
        ]);
        $path = getenv('PATH') ?: '';
        foreach (explode(PATH_SEPARATOR, $path) as $directory) {
            if ($directory !== '') $candidates[] = rtrim($directory, '\\/').DIRECTORY_SEPARATOR.(PHP_OS_FAMILY === 'Windows' ? 'pdftotext.exe' : 'pdftotext');
        }
        foreach ($candidates as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) return realpath($candidate) ?: $candidate;
        }
        throw new PlanParserException(
            'No se encontró pdftotext. Configure SISCO_PDFTOTEXT_PATH con una ruta fija.',
            'EXTRACTOR_NOT_FOUND',
            4
        );
    }
}

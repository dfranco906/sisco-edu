<?php
declare(strict_types=1);

namespace SiscoEdu\PlanImport\Pdf;

use SiscoEdu\PlanImport\Contracts\PdfTextExtractorInterface;
use SiscoEdu\PlanImport\PlanImportException;
use SiscoEdu\PlanImport\ProcessRunner;

final class XpdfTableExtractor implements PdfTextExtractorInterface
{
    private ?string $version = null;

    /** @param array<string,mixed> $config */
    public function __construct(private readonly array $config, private readonly ProcessRunner $runner = new ProcessRunner())
    {
    }

    /** @return array<string,mixed> */
    public function extract(string $pdfPath): array
    {
        $realPath = $this->validatePdf($pdfPath);
        $binary = $this->binary();
        $limits = $this->limits();
        $version = $this->identifyVersion($binary, $limits);
        $result = $this->runner->run(
            [$binary, '-enc', 'UTF-8', '-table', '--', $realPath, '-'],
            (float) $limits['timeout_seconds'],
            (int) $limits['max_output_bytes']
        );
        if ($result['exit_code'] !== 0) {
            throw new PlanImportException(
                'Xpdf no pudo extraer el documento.',
                'PDF_EXTRACTION_FAILED',
                5,
                422,
                ['exit_code'=>$result['exit_code'], 'stderr'=>$this->safeDiagnostic($result['stderr'])]
            );
        }
        $text = $result['stdout'];
        $printable = preg_replace('/[^\p{L}\p{N}]+/u', '', $text) ?? '';
        if (mb_strlen($printable, 'UTF-8') < 40) {
            throw new PlanImportException(
                'Este PDF parece ser una imagen escaneada. Utilice un PDF digital o cargue el plan manualmente.',
                'PDF_SCAN_NOT_SUPPORTED',
                3,
                422
            );
        }
        $pages = $this->pageCount($text);
        if ($pages > (int) $limits['max_pages']) {
            throw new PlanImportException('El PDF supera el máximo de páginas permitido.', 'PDF_PAGE_LIMIT', 2, 422, ['pages'=>$pages]);
        }
        return [
            'path'=>$realPath,
            'filename'=>basename($realPath),
            'sha256'=>hash_file('sha256', $realPath),
            'pages'=>$pages,
            'text'=>$text,
            'lines'=>$this->lineCoordinates($text),
            'extractor'=>[
                'name'=>'pdftotext',
                'mode'=>'table',
                'version'=>$version,
                'elapsed_ms'=>$result['elapsed_ms'],
            ],
        ];
    }

    private function validatePdf(string $pdfPath): string
    {
        $realPath = realpath($pdfPath);
        if ($realPath === false || !is_file($realPath)) throw new PlanImportException('El archivo PDF no existe.', 'PDF_NOT_FOUND', 2, 404);
        if (is_link($pdfPath)) throw new PlanImportException('No se permiten enlaces simbólicos como archivo de entrada.', 'PDF_SYMLINK_NOT_ALLOWED', 2, 422);
        if (strtolower(pathinfo($realPath, PATHINFO_EXTENSION)) !== 'pdf') throw new PlanImportException('La extensión del archivo debe ser .pdf.', 'INVALID_EXTENSION', 2, 422);
        $size = filesize($realPath);
        if ($size === false || $size < 5) throw new PlanImportException('El PDF está vacío.', 'INVALID_FILE_SIZE', 2, 422);
        if ($size > (int) $this->limits()['max_file_bytes']) throw new PlanImportException('El PDF supera el límite de 10 MB.', 'INVALID_FILE_SIZE', 2, 413);
        $handle = fopen($realPath, 'rb');
        $signature = is_resource($handle) ? fread($handle, 5) : false;
        if (is_resource($handle)) fclose($handle);
        if ($signature !== '%PDF-') throw new PlanImportException('La firma real del archivo no corresponde a un PDF.', 'INVALID_PDF_SIGNATURE', 2, 422);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($realPath);
        if (!in_array($mime, $this->config['allowed_mime_types'] ?? [], true)) {
            throw new PlanImportException('El MIME real del archivo no está permitido.', 'INVALID_MIME', 2, 422, ['mime'=>$mime]);
        }
        return $realPath;
    }

    private function binary(): string
    {
        $configured = trim((string) ($this->config['pdftotext_binary'] ?? ''));
        if ($configured === '' || !is_file($configured) || !is_readable($configured)) {
            throw new PlanImportException('El extractor PDF no está configurado en el servidor.', 'EXTRACTOR_NOT_FOUND', 4, 503);
        }
        return realpath($configured) ?: $configured;
    }

    /** @return array<string,int|float> */
    private function limits(): array
    {
        $limits = $this->config['limits'] ?? null;
        $required = ['max_file_bytes','max_pages','timeout_seconds','max_output_bytes'];
        if (!is_array($limits)) throw new PlanImportException('Límites del extractor no configurados.', 'EXTRACTOR_CONFIG_ERROR', 4, 500);
        foreach ($required as $key) if (!isset($limits[$key]) || !is_numeric($limits[$key]) || $limits[$key] <= 0) throw new PlanImportException('Límite inválido: '.$key, 'EXTRACTOR_CONFIG_ERROR', 4, 500);
        return $limits;
    }

    /** @param array<string,int|float> $limits */
    private function identifyVersion(string $binary, array $limits): string
    {
        if ($this->version !== null) return $this->version;
        $result = $this->runner->run([$binary, '-v'], min(3.0, (float) $limits['timeout_seconds']), 65536);
        $text = $result['stdout'].PHP_EOL.$result['stderr'];
        if (preg_match('/pdftotext\s+version\s+([0-9.]+)/i', $text, $match) !== 1 || !str_contains($text, 'Glyph & Cog')) {
            throw new PlanImportException('El ejecutable no es la versión Xpdf validada.', 'EXTRACTOR_VERSION_UNSUPPORTED', 4, 503);
        }
        return $this->version = $match[1];
    }

    private function pageCount(string $text): int
    {
        $count = substr_count($text, "\f") + (str_ends_with($text, "\f") ? 0 : 1);
        return max(1, $count);
    }

    /** @return array<int,array<string,mixed>> */
    private function lineCoordinates(string $text): array
    {
        $pages = explode("\f", $text);
        while ($pages && trim((string) end($pages)) === '') array_pop($pages);
        $result = [];
        foreach ($pages as $pageIndex => $pageText) {
            $lines = preg_split('/\R/u', $pageText) ?: [];
            $width = max(1, ...array_map(static fn(string $line): int => mb_strlen($line, 'UTF-8'), $lines));
            foreach ($lines as $lineIndex => $line) {
                if (trim($line) === '') continue;
                preg_match('/^(\s*)/u', $line, $leading);
                $start = mb_strlen($leading[1] ?? '', 'UTF-8');
                $end = mb_strlen(rtrim($line), 'UTF-8');
                $result[] = [
                    'page'=>$pageIndex + 1, 'line'=>$lineIndex + 1, 'text'=>rtrim($line),
                    'x_char_start'=>$start, 'x_char_end'=>$end,
                    'x_relative_start'=>round($start / $width, 6),
                    'x_relative_end'=>round($end / $width, 6),
                ];
            }
        }
        return $result;
    }

    private function safeDiagnostic(string $stderr): string
    {
        $clean = trim((string) preg_replace('/\s+/u', ' ', $stderr));
        return mb_substr($clean, 0, 1000, 'UTF-8');
    }
}

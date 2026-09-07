<?php
declare(strict_types=1);

namespace SiscoEdu\PlanParserPoc;

abstract class AbstractTableParser implements PlanPdfParserInterface
{
    /** @return array<int,array{page:int,line:int,seq:int,text:string}> */
    protected function rows(string $text): array
    {
        $pages = explode("\f", $text);
        while ($pages && trim((string) end($pages)) === '') array_pop($pages);
        $rows = [];
        $seq = 0;
        foreach ($pages as $pageIndex => $page) {
            $lines = preg_split('/\R/u', $page) ?: [];
            foreach ($lines as $lineIndex => $line) {
                $seq++;
                $rows[] = [
                    'page' => $pageIndex + 1,
                    'line' => $lineIndex + 1,
                    'seq' => $seq,
                    'text' => rtrim($line),
                ];
            }
        }
        return $rows;
    }

    /** @param array<int,array<string,mixed>> $rows @return array<string,mixed> */
    protected function metadata(array $rows, string $format): array
    {
        $metadata = [
            'institucion' => null,
            'materia' => null,
            'profesor' => null,
            'curso' => null,
            'turno' => null,
            'anio' => null,
            'dias_clase' => null,
            'competencia_general' => null,
            'competencia_especifica' => null,
        ];
        foreach ($rows as $row) {
            $line = (string) $row['text'];
            $metadata['institucion'] ??= $this->labelValue($line, 'INSTITUCI[ÓO]N', ['PROFESOR','CURSO(?:/GRADO)?','TURNO','A[ÑN]O']);
            $metadata['materia'] ??= $this->labelValue($line, 'MATERIA', ['CURSO(?:/GRADO)?','PROFESOR','D[ÍI]AS DE CLASE','A[ÑN]O']);
            $metadata['profesor'] ??= $this->labelValue($line, 'PROFESOR', ['CURSO(?:/GRADO)?','TURNO','D[ÍI]AS DE CLASE','A[ÑN]O']);
            $metadata['curso'] ??= $this->labelValue($line, 'CURSO(?:/GRADO)?', ['TURNO','D[ÍI]AS DE CLASE','A[ÑN]O']);
            $metadata['turno'] ??= $this->labelValue($line, 'TURNO', ['A[ÑN]O','D[ÍI]AS DE CLASE']);
            $metadata['dias_clase'] ??= $this->labelValue($line, 'D[ÍI]AS DE CLASE', ['A[ÑN]O']);
            if ($metadata['anio'] === null && preg_match('/\bA[ÑN]O\s*:\s*((?:19|20|21)\d{2})/ui', $line, $match) === 1) {
                $metadata['anio'] = (int) $match[1];
            }
            if ($metadata['institucion'] === null && preg_match('/^\s*(COLEGIO\s+[“"].+?[”"])/ui', $line, $match) === 1) {
                $metadata['institucion'] = Text::clean($match[1]);
            }
        }
        if ($metadata['materia'] === null) {
            if ($format === 'ADMINISTRACION_FINANCIERA') $metadata['materia'] = 'ADMINISTRACIÓN FINANCIERA';
            if ($format === 'EXCEL_AVANZADO') $metadata['materia'] = 'MICROSOFT EXCEL';
        }
        $metadata['competencia_general'] = $this->section(
            $rows,
            '/^\s*COMPETENCIA\s+GENERAL\b/ui',
            ['/COMPETENCIA\s+ESPEC[ÍI]FICA/ui','/UNIDAD\s+TEM[ÁA]TICA/ui','/^\s*UNIDAD\s+CAPACIDADES/ui']
        );
        $metadata['competencia_especifica'] = $this->section(
            $rows,
            '/^\s*COMPETENCIA\s+ESPEC[ÍI]FICA\b/ui',
            ['/PROCESO\s+PARA/ui','/PROCEDIMIENTOS.*INSTRUMENTOS/ui','/UNIDAD\s+TEM[ÁA]TICA/ui','/^\s*UNIDAD\s+CAPACIDADES/ui']
        );
        return $metadata;
    }

    /** @param array<int,array<string,mixed>> $rows @param array<int,string> $stopPatterns */
    private function section(array $rows, string $startPattern, array $stopPatterns): ?string
    {
        $started = false;
        $parts = [];
        foreach ($rows as $row) {
            $line = (string) $row['text'];
            if (!$started) {
                if (preg_match($startPattern, $line) !== 1) continue;
                $started = true;
                $rest = preg_replace($startPattern, '', $line, 1);
                if (Text::clean($rest) !== '') $parts[] = Text::clean($rest);
                continue;
            }
            foreach ($stopPatterns as $pattern) {
                if (preg_match($pattern, $line) === 1) return Text::join($parts);
            }
            if (trim($line) !== '') $parts[] = Text::clean($line);
        }
        return Text::join($parts);
    }

    /** @param array<int,string> $nextLabels */
    private function labelValue(string $line, string $label, array $nextLabels): ?string
    {
        $next = $nextLabels ? '(?=\s+(?:'.implode('|', $nextLabels).')\s*:|$)' : '$';
        if (preg_match('~'.$label.'\s*:\s*(.*?)'.$next.'~ui', $line, $match) !== 1) return null;
        $value = Text::clean($match[1]);
        return $value === '' ? null : $value;
    }

    protected function column(array $row, int $start, ?int $end = null): string
    {
        $length = $end === null ? null : max(0, $end - $start);
        return Text::clean(Text::slice((string) $row['text'], $start, $length));
    }

    /** @param array<int,array<string,mixed>> $rows */
    protected function firstHeaderIndex(array $rows, array $needles): ?int
    {
        foreach ($rows as $index => $row) {
            $key = Text::key((string) $row['text']);
            $matches = true;
            foreach ($needles as $needle) {
                if (!str_contains($key, Text::key($needle))) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) return $index;
        }
        return null;
    }

    /** @param array<int,array<string,mixed>> $rows */
    protected function labelPositionNear(array $rows, int $headerIndex, array $aliases, int $radius = 3): ?int
    {
        $from = max(0, $headerIndex - $radius);
        $to = min(count($rows) - 1, $headerIndex + $radius);
        for ($i = $from; $i <= $to; $i++) {
            foreach ($aliases as $alias) {
                $position = Text::position((string) $rows[$i]['text'], $alias);
                if ($position !== null) return $position;
            }
        }
        return null;
    }

    /** @param array<int,array<string,mixed>> $rows */
    protected function minimumRegexPosition(array $rows, string $pattern): ?int
    {
        $minimum = null;
        foreach ($rows as $row) {
            if (preg_match($pattern, (string) $row['text'], $match, PREG_OFFSET_CAPTURE) !== 1) continue;
            $byteOffset = $match[0][1];
            $position = mb_strlen(substr((string) $row['text'], 0, $byteOffset), 'UTF-8');
            $minimum = $minimum === null ? $position : min($minimum, $position);
        }
        return $minimum;
    }

    /** @param array<int,array<string,mixed>> $markers @return array<int,array<string,mixed>> */
    protected function markerRanges(array $markers): array
    {
        usort($markers, static fn(array $a, array $b): int => $a['row']['seq'] <=> $b['row']['seq']);
        foreach ($markers as $index => &$marker) {
            $current = (int) $marker['row']['seq'];
            $previous = $markers[$index - 1]['row']['seq'] ?? null;
            $next = $markers[$index + 1]['row']['seq'] ?? null;
            $marker['from'] = $previous === null ? PHP_INT_MIN : (int) floor(($previous + $current) / 2) + 1;
            $marker['to'] = $next === null ? PHP_INT_MAX : (int) floor(($current + $next) / 2);
        }
        unset($marker);
        return $markers;
    }

    /** @param array<int,array<string,mixed>> $rows @return array<int,array<string,mixed>> */
    protected function rowsInRange(array $rows, int $from, int $to): array
    {
        return array_values(array_filter(
            $rows,
            static fn(array $row): bool => $row['seq'] >= $from && $row['seq'] <= $to
        ));
    }

    protected function isFooter(array $row): bool
    {
        return preg_match('/^\s*\d{1,2}\/\d{1,2}\/\d{4}\b/u', (string) $row['text']) === 1;
    }

    /** @param array<int,array<string,mixed>> $fragments @return array<int,array<string,mixed>> */
    protected function groupBulletFragments(array $fragments): array
    {
        $items = [];
        foreach ($fragments as $fragment) {
            $text = Text::clean((string) $fragment['text']);
            if ($text === '') continue;
            if (preg_match('/^[\-•·]\s*(.+)$/u', $text, $match) === 1) {
                $items[] = ['row' => $fragment['row'], 'parts' => [Text::clean($match[1])]];
            } elseif ($items) {
                $items[array_key_last($items)]['parts'][] = $text;
            } else {
                $items[] = ['row' => $fragment['row'], 'parts' => [$text]];
            }
        }
        foreach ($items as &$item) $item['text'] = Text::join($item['parts']) ?? '';
        unset($item);
        return $items;
    }

    /** @return array{number:?float,text:?string} */
    protected function hours(?string $text): array
    {
        $text = Text::clean($text);
        if ($text === '') return ['number' => null, 'text' => null];
        if (preg_match('/(?<!\d)(\d+(?:[.,]\d+)?)\s*(?:HC|H\b|HORAS?)/ui', $text, $match) !== 1) {
            if (preg_match('/^\d+(?:[.,]\d+)?$/u', $text) !== 1) return ['number' => null, 'text' => $text];
            return ['number' => (float) str_replace(',', '.', $text), 'text' => $text];
        }
        return ['number' => (float) str_replace(',', '.', $match[1]), 'text' => $text];
    }
}

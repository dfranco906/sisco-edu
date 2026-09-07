<?php
declare(strict_types=1);

namespace SiscoEdu\PlanParserPoc;

final class AdministracionFinancieraParser extends AbstractTableParser
{
    public function format(): string
    {
        return 'ADMINISTRACION_FINANCIERA';
    }

    public function parse(array $document): array
    {
        $plan = CanonicalPlan::create($document, $this->format());
        $rows = $this->rows((string) $document['text']);
        $plan['metadata'] = $this->metadata($rows, $this->format());
        $headerIndex = $this->firstHeaderIndex($rows, ['Unidad Temática', 'Capacidades', 'Temas', 'Indicadores']);
        if ($headerIndex === null) {
            $plan['warnings'][] = Text::warning('TABLE_STRUCTURE_WARNING', 'No se encontró el encabezado principal del formato financiero.');
            return $plan;
        }

        $starts = [
            'unit' => $this->labelPositionNear($rows, $headerIndex, ['Unidad Temática']) ?? 0,
            'capacity' => $this->labelPositionNear($rows, $headerIndex, ['Capacidades']) ?? 30,
            'topic' => $this->labelPositionNear($rows, $headerIndex, ['Temas']) ?? 60,
            'indicator' => $this->labelPositionNear($rows, $headerIndex, ['Indicadores']) ?? 95,
            'procedure' => $this->labelPositionNear($rows, $headerIndex, ['Procedimientos']) ?? 135,
            'instrument' => $this->labelPositionNear($rows, $headerIndex, ['Instrumentos']) ?? 165,
            'time' => $this->labelPositionNear($rows, $headerIndex, ['Tiempo']) ?? 195,
            'process' => $this->labelPositionNear($rows, $headerIndex, ['Proceso']) ?? 210,
        ];
        $headerSeq = (int) $rows[$headerIndex]['seq'];
        $dataRows = array_values(array_filter($rows, function(array $row) use ($headerSeq): bool {
            if ($row['seq'] <= $headerSeq || $this->isFooter($row)) return false;
            $key = Text::key((string) $row['text']);
            if ($key === '') return false;
            return !str_contains($key, 'UNIDAD TEMATICA')
                && !str_contains($key, 'PROCEDIMIENTOS')
                && !str_contains($key, 'CATEDRA)');
        }));

        $markers = [];
        foreach ($dataRows as $row) {
            $cell = $this->column($row, $starts['unit'], $starts['capacity']);
            if (preg_match('/^([IVXLCDM]+)\.\s*(.*)$/ui', $cell, $match) !== 1) continue;
            $markers[] = ['row' => $row, 'code' => strtoupper($match[1]), 'first_name' => Text::clean($match[2])];
        }
        if (!$markers) {
            $plan['warnings'][] = Text::warning('TABLE_STRUCTURE_WARNING', 'No se detectaron unidades con numeración romana.');
            $plan['debug'] = ['columns' => $this->debugColumns($starts, $rows), 'unit_markers' => []];
            return $plan;
        }

        $ranges = $this->markerRanges($markers);
        $allIndicatorFragments = [];
        foreach ($dataRows as $row) {
            $this->addFragment($allIndicatorFragments, $row, $this->column($row, $starts['indicator'], $starts['procedure']));
        }
        $allIndicators = $this->groupBulletFragments($allIndicatorFragments);

        foreach ($ranges as $markerIndex => $marker) {
            $unitRows = $this->rowsInRange($dataRows, $marker['from'], $marker['to']);
            $unitParts = [];
            $capacityParts = [];
            $topicFragments = [];
            $procedureParts = [];
            $instrumentParts = [];
            $timeParts = [];
            $processParts = [];
            foreach ($unitRows as $row) {
                $unitText = $this->column($row, $starts['unit'], $starts['capacity']);
                if ($unitText !== '') {
                    $unitText = preg_replace('/^[IVXLCDM]+\.\s*/ui', '', $unitText, 1) ?? $unitText;
                    if (Text::clean($unitText) !== '') $unitParts[] = ['row' => $row, 'text' => $unitText];
                }
                $this->addFragment($capacityParts, $row, $this->column($row, $starts['capacity'], $starts['topic']));
                $this->addFragment($topicFragments, $row, $this->column($row, $starts['topic'], $starts['indicator']));
                $this->addFragment($procedureParts, $row, $this->column($row, $starts['procedure'], $starts['instrument']));
                $this->addFragment($instrumentParts, $row, $this->column($row, $starts['instrument'], $starts['time']));
                $this->addFragment($timeParts, $row, $this->column($row, $starts['time'], $starts['process']));
                $this->addFragment($processParts, $row, $this->column($row, $starts['process']));
            }
            $unitName = Text::join($unitParts) ?? ('UNIDAD '.$marker['code']);
            $unit = CanonicalPlan::unit($markerIndex + 1, $marker['code'], $unitName);
            $time = $this->hours(Text::join($timeParts));
            $unit['horas_catedra'] = $time['number'];
            $unit['tiempo_texto'] = $time['text'];
            $unit['proceso_texto'] = Text::join($processParts);
            $capacityText = Text::join($capacityParts) ?? '';
            $capacity = CanonicalPlan::capacity(1, $capacityText);
            $topics = $this->topics($topicFragments);
            $indicators = array_values(array_filter($allIndicators, static fn(array $indicator): bool =>
                $indicator['row']['seq'] >= $marker['from'] && $indicator['row']['seq'] <= $marker['to']
            ));
            $procedures = Text::splitList(Text::join($procedureParts));
            $instruments = Text::splitList(Text::join($instrumentParts));

            if (count($topics) === 1) {
                foreach ($indicators as $indicatorIndex => $indicator) {
                    $topics[0]['indicadores'][] = CanonicalPlan::indicator($indicatorIndex + 1, null, $indicator['text']);
                }
            } elseif (count($topics) === count($indicators)) {
                foreach ($topics as $topicIndex => &$topic) {
                    $topic['indicadores'][] = CanonicalPlan::indicator(1, null, $indicators[$topicIndex]['text']);
                }
                unset($topic);
            } else {
                foreach ($indicators as $indicator) {
                    $plan['warnings'][] = Text::warning(
                        'ORPHAN_INDICATOR',
                        'La cantidad de indicadores no coincide con la de temas; se evitó una asociación insegura.',
                        $indicator['row'],
                        $indicator['text']
                    );
                }
            }
            foreach ($topics as &$topic) {
                $topic['procedimientos_evaluativos'] = $procedures;
                $topic['instrumentos_evaluativos'] = $instruments;
                $topic['horas_catedra'] = count($topics) === 1 ? $unit['horas_catedra'] : null;
                $topic['tiempo_texto'] = count($topics) === 1 ? $unit['tiempo_texto'] : null;
            }
            unset($topic);
            $capacity['temas'] = $topics;
            $unit['capacidades'][] = $capacity;
            $plan['unidades'][] = $unit;
        }

        $plan['debug'] = [
            'columns' => $this->debugColumns($starts, $rows),
            'unit_markers' => array_map(static fn(array $x): array => [
                'code' => $x['code'], 'page' => $x['row']['page'], 'line' => $x['row']['line'],
            ], $markers),
            'association_rule' => 'Celdas verticales por punto medio entre unidades; tema/indicador por orden solo cuando las cantidades coinciden.',
        ];
        return $plan;
    }

    /** @param array<int,array<string,mixed>> $target */
    private function addFragment(array &$target, array $row, string $text): void
    {
        if ($text !== '') $target[] = ['row' => $row, 'text' => $text];
    }

    /** @param array<int,array<string,mixed>> $fragments @return array<int,array<string,mixed>> */
    private function topics(array $fragments): array
    {
        $coded = [];
        foreach ($fragments as $fragment) {
            $text = Text::clean((string) $fragment['text']);
            if (preg_match('/^(?:TEMA\s*)?(\d+\.\d+)\b\s*-?\s*(.*)$/ui', $text, $match) === 1) {
                $coded[] = ['code' => $match[1], 'row' => $fragment['row'], 'parts' => [Text::clean($match[2])]];
            } elseif ($coded) {
                $coded[array_key_last($coded)]['parts'][] = $text;
            }
        }
        if (!$coded) {
            $text = Text::join($fragments);
            return $text === null ? [] : [CanonicalPlan::topic(1, null, $text)];
        }
        $topics = [];
        foreach ($coded as $index => $item) {
            $topics[] = CanonicalPlan::topic($index + 1, $item['code'], Text::join($item['parts']) ?? '');
        }
        return $topics;
    }

    /** @param array<string,int> $starts @param array<int,array<string,mixed>> $rows */
    private function debugColumns(array $starts, array $rows): array
    {
        $width = 1;
        foreach ($rows as $row) $width = max($width, mb_strlen((string) $row['text'], 'UTF-8'));
        $debug = [];
        $names = array_keys($starts);
        foreach ($names as $index => $name) {
            $nextName = $names[$index + 1] ?? null;
            $end = $nextName === null ? $width : $starts[$nextName];
            $debug[$name] = [
                'x_char_start' => $starts[$name],
                'x_char_end' => $end,
                'x_relative_start' => round($starts[$name] / $width, 4),
                'x_relative_end' => round($end / $width, 4),
            ];
        }
        return $debug;
    }
}

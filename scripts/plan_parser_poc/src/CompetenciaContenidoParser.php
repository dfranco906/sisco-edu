<?php
declare(strict_types=1);

namespace SiscoEdu\PlanParserPoc;

final class CompetenciaContenidoParser extends AbstractTableParser
{
    public function format(): string
    {
        return 'COMPETENCIA_CONTENIDO';
    }

    public function parse(array $document): array
    {
        $plan = CanonicalPlan::create($document, $this->format());
        $rows = $this->rows((string) $document['text']);
        $plan['metadata'] = $this->metadata($rows, $this->format());
        $pageStarts = [];
        $headerSeqByPage = [];
        foreach (range(1, (int) $document['pages']) as $page) {
            $pageRows = array_values(array_filter($rows, static fn(array $row): bool => $row['page'] === $page));
            $headerIndex = $this->firstHeaderIndex($pageRows, ['Competencia', 'Capacidad', 'Indicadores', 'Contenidos']);
            if ($headerIndex === null) continue;
            $header = $pageRows[$headerIndex];
            $headerSeqByPage[$page] = (int) $header['seq'];
            $bullet = $this->minimumBulletPosition(array_slice($pageRows, $headerIndex + 1));
            $pageStarts[$page] = [
                'competence' => 0,
                'capacity' => Text::position((string) $header['text'], 'CAPACIDAD') ?? 30,
                'indicator' => $bullet ?? max(50, (Text::position((string) $header['text'], 'INDICADORES') ?? 84) - 15),
                'content' => Text::position((string) $header['text'], 'CONTENIDOS') ?? 118,
                'area' => Text::position((string) $header['text'], 'ÁREA') ?? 145,
                'method' => Text::position((string) $header['text'], 'METODOLOGÍA') ?? 170,
                'means' => Text::position((string) $header['text'], 'MEDIOS') ?? 190,
                'date' => Text::position((string) $header['text'], 'FECHA') ?? 206,
            ];
        }
        if (!$pageStarts) {
            $plan['warnings'][] = Text::warning('TABLE_STRUCTURE_WARNING', 'No se encontraron encabezados del formato competencia/contenido.');
            return $plan;
        }
        $dataRows = array_values(array_filter($rows, function(array $row) use ($headerSeqByPage): bool {
            if (!isset($headerSeqByPage[$row['page']]) || $row['seq'] <= $headerSeqByPage[$row['page']]) return false;
            if ($this->isFooter($row) || trim((string) $row['text']) === '') return false;
            return true;
        }));

        $unitFragments = $this->fragmentsFor($dataRows, $pageStarts, 'competence', 'capacity');
        $unitGroups = $this->groupByGap($unitFragments, 3);
        if (!$unitGroups) {
            $plan['warnings'][] = Text::warning('TABLE_STRUCTURE_WARNING', 'No se detectaron bloques de competencia para usarlos como unidades.');
            return $plan;
        }
        $units = [];
        foreach ($unitGroups as $index => $group) {
            $name = Text::join($group['parts']) ?? '';
            $units[] = [
                'row' => $group['first_row'],
                'data' => CanonicalPlan::unit($index + 1, null, $name),
                'field_parts' => ['area'=>[], 'method'=>[], 'means'=>[], 'date'=>[]],
            ];
        }

        $capacityFragments = $this->fragmentsFor($dataRows, $pageStarts, 'capacity', 'indicator');
        $capacityGroups = $this->mergeRepeatedPageContinuation($this->groupByGap($capacityFragments, 3));
        $capacityGroups = $this->withGroupRanges($capacityGroups);
        $contentFragments = $this->fragmentsFor($dataRows, $pageStarts, 'content', 'area');
        $contentGroups = $this->groupContentFragments($contentFragments);
        $indicatorFragments = $this->fragmentsFor($dataRows, $pageStarts, 'indicator', 'content');
        $indicators = $this->groupBulletFragments($indicatorFragments);

        foreach ($capacityGroups as $group) {
            $unitIndex = $this->precedingUnitIndex($units, (int) $group['first_row']['seq']);
            $capacity = CanonicalPlan::capacity(count($units[$unitIndex]['data']['capacidades']) + 1, Text::join($group['parts']) ?? '');
            $topicParts = [];
            foreach ($contentGroups as $contentGroup) {
                if ($contentGroup['center'] >= $group['from'] && $contentGroup['center'] <= $group['to']) {
                    foreach ($contentGroup['parts'] as $fragment) $topicParts[] = $fragment;
                }
            }
            $topicText = Text::join($topicParts);
            if ($topicText !== null) {
                $topic = CanonicalPlan::topic(1, null, $topicText);
                foreach ($indicators as $indicator) {
                    $seq = (int) $indicator['row']['seq'];
                    if ($seq < $group['from'] || $seq > $group['to']) continue;
                    $topic['indicadores'][] = CanonicalPlan::indicator(count($topic['indicadores']) + 1, null, $indicator['text']);
                }
                $capacity['temas'][] = $topic;
            } else {
                foreach ($indicators as $indicator) {
                    $seq = (int) $indicator['row']['seq'];
                    if ($seq < $group['from'] || $seq > $group['to']) continue;
                    $plan['warnings'][] = Text::warning('ORPHAN_INDICATOR', 'La capacidad no tiene contenido utilizable; no se inventó un tema.', $indicator['row'], $indicator['text']);
                }
            }
            $units[$unitIndex]['data']['capacidades'][] = $capacity;
        }

        foreach (['area'=>'area_transversal', 'method'=>'metodologia', 'means'=>'medios_verificacion', 'date'=>'proceso_texto'] as $column => $target) {
            $end = ['area'=>'method', 'method'=>'means', 'means'=>'date', 'date'=>null][$column];
            foreach ($this->fragmentsFor($dataRows, $pageStarts, $column, $end) as $fragment) {
                $unitIndex = $this->precedingUnitIndex($units, (int) $fragment['row']['seq']);
                $units[$unitIndex]['field_parts'][$column][] = $fragment;
            }
            foreach ($units as &$unit) $unit['data'][$target] = Text::join($unit['field_parts'][$column], true);
            unset($unit);
        }
        foreach ($units as $unit) $plan['unidades'][] = $unit['data'];

        $plan['warnings'][] = Text::warning(
            'TABLE_STRUCTURE_WARNING',
            'Los CONTENIDOS sin numeración se agruparon de forma conservadora: un tema compuesto por cada capacidad. La preview humana debe decidir si los subdivide.'
        );
        $plan['debug'] = [
            'columns_by_page' => $this->debugColumnsByPage($pageStarts, $rows),
            'unit_markers' => array_map(static fn(array $unit): array => [
                'name' => $unit['data']['nombre'], 'page' => $unit['row']['page'], 'line' => $unit['row']['line'],
            ], $units),
            'capacity_groups' => count($capacityGroups),
            'capacity_ranges' => array_map(static fn(array $group): array => [
                'description'=>Text::join($group['parts']),
                'first'=>['page'=>$group['first_row']['page'],'line'=>$group['first_row']['line'],'seq'=>$group['first_row']['seq']],
                'last'=>['page'=>$group['last_row']['page'],'line'=>$group['last_row']['line'],'seq'=>$group['last_row']['seq']],
                'from'=>$group['from'],'to'=>$group['to'],
            ], $capacityGroups),
            'indicator_starts' => array_map(static fn(array $indicator): array => [
                'text'=>$indicator['text'],'page'=>$indicator['row']['page'],'line'=>$indicator['row']['line'],'seq'=>$indicator['row']['seq'],
            ], $indicators),
            'association_rule' => 'Capacidad por separación vertical; contenido compuesto e indicadores asociados solo dentro del intervalo seguro de esa capacidad.',
        ];
        return $plan;
    }

    /** @param array<int,array<string,mixed>> $rows */
    private function minimumBulletPosition(array $rows): ?int
    {
        $minimum = null;
        foreach ($rows as $row) {
            foreach (['·','•'] as $bullet) {
                $position = Text::position((string) $row['text'], $bullet);
                if ($position !== null) $minimum = $minimum === null ? $position : min($minimum, $position);
            }
        }
        return $minimum;
    }

    /** @param array<int,array<string,mixed>> $rows @param array<int,array<string,int>> $pageStarts @return array<int,array<string,mixed>> */
    private function fragmentsFor(array $rows, array $pageStarts, string $startName, ?string $endName): array
    {
        $fragments = [];
        foreach ($rows as $row) {
            $starts = $pageStarts[$row['page']] ?? null;
            if ($starts === null) continue;
            $text = $this->column($row, $starts[$startName], $endName === null ? null : $starts[$endName]);
            if (in_array(Text::key($text), ['DE ENSENANZA','VERIFICACION'], true)) continue;
            if ($text !== '') $fragments[] = ['row' => $row, 'text' => $text];
        }
        return $fragments;
    }

    /** @param array<int,array<string,mixed>> $fragments @return array<int,array<string,mixed>> */
    private function groupByGap(array $fragments, int $maximumGap): array
    {
        $groups = [];
        foreach ($fragments as $fragment) {
            $seq = (int) $fragment['row']['seq'];
            if (!$groups || $seq - (int) $groups[array_key_last($groups)]['last_row']['seq'] > $maximumGap) {
                $groups[] = ['first_row'=>$fragment['row'], 'last_row'=>$fragment['row'], 'parts'=>[$fragment]];
            } else {
                $groups[array_key_last($groups)]['last_row'] = $fragment['row'];
                $groups[array_key_last($groups)]['parts'][] = $fragment;
            }
        }
        return $groups;
    }

    /** @param array<int,array<string,mixed>> $fragments @return array<int,array<string,mixed>> */
    private function groupContentFragments(array $fragments): array
    {
        $groups = [];
        foreach ($fragments as $fragment) {
            $text = Text::clean((string) $fragment['text']);
            $append = false;
            if ($groups) {
                $last = array_key_last($groups);
                $lastPartIndex = array_key_last($groups[$last]['parts']);
                $previous = Text::clean((string) $groups[$last]['parts'][$lastPartIndex]['text']);
                $gap = (int) $fragment['row']['seq'] - (int) $groups[$last]['last_row']['seq'];
                $append = $gap <= 20 && (
                    preg_match('/(?:\bde|\by|,)\s*$/ui', $previous) === 1
                    || preg_match('/^[\p{Ll}]/u', $text) === 1
                );
            }
            if ($append) {
                $groups[$last]['parts'][] = $fragment;
                $groups[$last]['last_row'] = $fragment['row'];
            } else {
                $groups[] = ['first_row'=>$fragment['row'], 'last_row'=>$fragment['row'], 'parts'=>[$fragment]];
            }
        }
        foreach ($groups as &$group) {
            $group['center'] = (int) floor(((int)$group['first_row']['seq'] + (int)$group['last_row']['seq']) / 2);
        }
        unset($group);
        return $groups;
    }

    /** @param array<int,array<string,mixed>> $groups @return array<int,array<string,mixed>> */
    private function mergeRepeatedPageContinuation(array $groups): array
    {
        $merged = [];
        foreach ($groups as $group) {
            if ($merged) {
                $last = array_key_last($merged);
                $sameText = Text::key(Text::join($merged[$last]['parts'])) === Text::key(Text::join($group['parts']));
                $nextPage = $group['first_row']['page'] === $merged[$last]['last_row']['page'] + 1;
                if ($sameText && $nextPage) {
                    $merged[$last]['last_row'] = $group['last_row'];
                    continue;
                }
            }
            $merged[] = $group;
        }
        return $merged;
    }

    /** @param array<int,array<string,mixed>> $groups @return array<int,array<string,mixed>> */
    private function withGroupRanges(array $groups): array
    {
        foreach ($groups as $index => &$group) {
            $previousLast = $groups[$index - 1]['last_row']['seq'] ?? null;
            $nextFirst = $groups[$index + 1]['first_row']['seq'] ?? null;
            $first = (int) $group['first_row']['seq'];
            $last = (int) $group['last_row']['seq'];
            $group['from'] = $previousLast === null ? PHP_INT_MIN : (int) floor(($previousLast + $first) / 2) + 1;
            $group['to'] = $nextFirst === null ? PHP_INT_MAX : (int) floor(($last + $nextFirst) / 2);
        }
        unset($group);
        return $groups;
    }

    /** @param array<int,array<string,mixed>> $units */
    private function precedingUnitIndex(array $units, int $seq): int
    {
        $found = 0;
        foreach ($units as $index => $unit) {
            if ((int) $unit['row']['seq'] > $seq) break;
            $found = $index;
        }
        return $found;
    }

    /** @param array<int,array<string,int>> $pageStarts @param array<int,array<string,mixed>> $rows */
    private function debugColumnsByPage(array $pageStarts, array $rows): array
    {
        $debug = [];
        foreach ($pageStarts as $page => $starts) {
            $width = 1;
            foreach ($rows as $row) if ($row['page'] === $page) $width = max($width, mb_strlen((string) $row['text'], 'UTF-8'));
            $names = array_keys($starts);
            foreach ($names as $index => $name) {
                $nextName = $names[$index + 1] ?? null;
                $end = $nextName === null ? $width : $starts[$nextName];
                $debug[$page][$name] = [
                    'x_char_start'=>$starts[$name], 'x_char_end'=>$end,
                    'x_relative_start'=>round($starts[$name] / $width, 4),
                    'x_relative_end'=>round($end / $width, 4),
                ];
            }
        }
        return $debug;
    }
}

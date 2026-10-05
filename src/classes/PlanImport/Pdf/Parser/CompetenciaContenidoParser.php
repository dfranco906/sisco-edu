<?php
declare(strict_types=1);

namespace SiscoEdu\PlanImport\Pdf\Parser;

use SiscoEdu\PlanImport\CanonicalPlan;
use SiscoEdu\PlanImport\PlanImportException;
use SiscoEdu\PlanImport\Text;

final class CompetenciaContenidoParser extends AbstractTableParser
{
    public function format(): string { return 'COMPETENCIA_CONTENIDO'; }

    public function parse(array $document): array
    {
        $regions = $document['table_cells'] ?? null;
        if (!is_array($regions) || !$regions) {
            throw new PlanImportException('No se pudieron verificar las celdas del PDF; no se inferirán asociaciones.', 'PDF_TABLE_BOUNDARIES_UNSUPPORTED', 5, 422);
        }
        $plan = CanonicalPlan::create($document, $this->format());
        $plan['metadata'] = $this->metadata($this->rows((string)$document['text']), $this->format());
        $rows = [];
        $indicators = [];
        $unitBlocks = [];
        $dates = [];

        foreach ($regions as $region) {
            $cells = $region['cells'];
            if (Text::key($cells[0][0]['text'] ?? '') === 'COMPETENCIA' && count($cells[1]) === 1) continue;
            foreach ($cells[7] as $cell) {
                if (Text::key($cell['text']) !== 'FECHA') $dates[] = $cell + ['page'=>$region['page']];
            }
            foreach ($cells[2] as $cell) {
                if (Text::key($cell['text']) === 'INDICADORES') continue;
                foreach (preg_split('/\R/u', $cell['text']) ?: [] as $line) {
                    $line = Text::clean($line);
                    if ($line === '') continue;
                    if (preg_match('/^[·•-]\s*(.+)$/u', $line, $m)) {
                        $indicators[] = ['page'=>$region['page'],'box'=>$cell['box'],'parts'=>[$m[1]]];
                    } elseif ($indicators) {
                        // Wrapped text may cross a printed cell edge: the bullet identifies its owner.
                        $indicators[array_key_last($indicators)]['parts'][] = $line;
                    } else $this->ambiguous();
                }
            }
            foreach ($cells[0] as $cell) {
                if (Text::key($cell['text']) === 'COMPETENCIA') continue;
                $block = ['name'=>Text::join(preg_split('/\R/u', $cell['text']) ?: []), 'rows'=>[]];
                foreach ($cells[1] as $capacityCell) {
                    if (!$this->contains($cell['box'], $capacityCell['box'])) continue;
                    $row = ['page'=>$region['page'],'box'=>$capacityCell['box'],'description'=>$this->cellText($capacityCell),'contents'=>[], 'indicators'=>[], 'fields'=>[]];
                    foreach ($cells[3] as $content) if ($this->contains($row['box'], $content['box'])) $row['contents'][] = $this->cellText($content);
                    foreach ([4=>'area_transversal',5=>'metodologia',6=>'medios_verificacion'] as $column=>$field) {
                        foreach ($cells[$column] as $context) if ($this->contains($context['box'], $row['box'])) $row['fields'][$field][] = $this->cellText($context);
                    }
                    if ($row['description'] === null && !Text::join($row['contents'])) continue;
                    $rowIndex = count($rows);
                    $rows[] = $row;
                    $block['rows'][] = $rowIndex;
                }
                if ($block['rows']) $unitBlocks[] = $block;
            }
        }

        // A blank tail of a merged cell gets its label from the following page.
        foreach ($dates as $i=>&$date) {
            if (Text::clean($date['text']) === '' && isset($dates[$i+1]) && $dates[$i+1]['page'] === $date['page']+1) $date['text'] = $dates[$i+1]['text'];
        }
        unset($date);
        foreach ($indicators as $indicator) {
            $matches = [];
            foreach ($rows as $i=>$row) if ($row['page'] === $indicator['page'] && $this->contains($row['box'], $indicator['box'])) $matches[] = $i;
            if (count($matches) !== 1) $this->ambiguous();
            $rows[$matches[0]]['indicators'][] = Text::join($indicator['parts']);
        }
        foreach ($rows as &$row) {
            foreach ($dates as $date) if ($date['page'] === $row['page'] && $this->contains($date['box'], $row['box'])) $row['fields']['proceso_texto'][] = $this->cellText($date);
        }
        unset($row);

        $pending = [];
        $units = [];
        foreach ($unitBlocks as $block) {
            if ($block['name'] === null) { $pending = array_merge($pending, $block['rows']); continue; }
            $unitIndex = count($units)-1;
            if ($unitIndex < 0 || Text::key($units[$unitIndex]['data']['nombre']) !== Text::key($block['name'])) {
                $units[] = ['data'=>CanonicalPlan::unit(count($units)+1, null, $block['name']), 'rows'=>[]];
                $unitIndex = count($units)-1;
            }
            $units[$unitIndex]['rows'] = array_merge($units[$unitIndex]['rows'], $pending, $block['rows']);
            $pending = [];
        }
        if ($pending || !$units) $this->ambiguous();
        foreach ($units as $unit) {
            $data = $unit['data'];
            $merged = [];
            $fields = [];
            foreach ($unit['rows'] as $rowIndex) {
                $row = $rows[$rowIndex];
                $last = count($merged)-1;
                if ($last>=0 && $row['page'] === $merged[$last]['page']+1 &&
                    ($merged[$last]['description'] === null || Text::key($merged[$last]['description']) === Text::key($row['description'] ?? ''))) {
                    $merged[$last]['description'] ??= $row['description'];
                    $merged[$last]['contents'] = array_merge($merged[$last]['contents'], $row['contents']);
                    $merged[$last]['indicators'] = array_merge($merged[$last]['indicators'], $row['indicators']);
                    $merged[$last]['fields'] = array_merge_recursive($merged[$last]['fields'], $row['fields']);
                    $merged[$last]['page'] = $row['page'];
                } else $merged[] = $row;
            }
            foreach ($merged as $row) {
                if ($row['description'] === null || !Text::join($row['contents']) || !$row['indicators']) $this->ambiguous();
                $capacity = CanonicalPlan::capacity(count($data['capacidades'])+1, $row['description']);
                $topic = CanonicalPlan::topic(1, null, Text::join($row['contents']));
                $topic['fecha_texto'] = Text::join($row['fields']['proceso_texto'] ?? [], true);
                foreach ($row['indicators'] as $text) $topic['indicadores'][] = CanonicalPlan::indicator(count($topic['indicadores'])+1, null, $text);
                $capacity['temas'][] = $topic;
                $data['capacidades'][] = $capacity;
                $fields = array_merge_recursive($fields, $row['fields']);
            }
            foreach (['area_transversal','metodologia','medios_verificacion','proceso_texto'] as $field) $data[$field] = Text::join($fields[$field] ?? [], true);
            $plan['unidades'][] = $data;
        }
        $plan['warnings'][] = Text::warning('TABLE_STRUCTURE_WARNING','Los CONTENIDOS sin numeración se conservaron como un tema compuesto por capacidad; sus asociaciones se verificaron por los límites de las celdas.');
        if (str_contains(Text::key((string)$document['filename']), 'SEGUNDO CURSO') && preg_match('/^\s*1/', (string)$plan['metadata']['curso'])) {
            $plan['warnings'][] = Text::warning('SOURCE_METADATA_MISMATCH','El nombre del archivo sugiere curso 2, pero el PDF declara '.$plan['metadata']['curso'].'.',null,(string)$document['filename']);
        }
        $plan['debug'] = ['association_rule'=>'Límites de celdas impresos; continuación entre páginas; indicadores por inicio de viñeta.', 'table_regions'=>count($regions)];
        return $plan;
    }

    private function contains(array $outer, array $inner): bool
    {
        $center = ($inner[1]+$inner[3])/2;
        return $center > $outer[1]-0.05 && $center < $outer[3]+0.05;
    }

    private function cellText(array $cell): ?string { return Text::join(preg_split('/\R/u', $cell['text']) ?: []); }

    private function ambiguous(): never
    {
        throw new PlanImportException('La tabla contiene una asociación ambigua entre competencia, capacidad, contenido o indicador. Revise el PDF antes de importar.', 'PDF_TABLE_ASSOCIATION_AMBIGUOUS', 5, 422);
    }
}

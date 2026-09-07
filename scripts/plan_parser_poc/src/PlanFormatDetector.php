<?php
declare(strict_types=1);

namespace SiscoEdu\PlanParserPoc;

final class PlanFormatDetector
{
    public const MIN_SCORE = 60;
    public const MIN_MARGIN = 15;

    /** @return array<string,mixed> */
    public function detect(string $text): array
    {
        $key = Text::key($text);
        $rules = [
            'ADMINISTRACION_FINANCIERA' => [
                ['Título Administración Financiera', 25, '/ADMINISTRACION FINANCIERA\s*-\s*PLAN ANUAL/'],
                ['Unidad Temática y Capacidades', 20, '/UNIDAD TEMATICA\s+CAPACIDADES/'],
                ['Procedimientos Evaluativos', 15, '/PROCEDIMIENTOS.{0,220}EVALUATIVOS/'],
                ['Instrumentos Evaluativos', 15, '/INSTRUMENTOS.{0,220}EVALUATIVOS/'],
                ['Tiempo HC/Horas Cátedra', 10, '/TIEMPO.{0,220}(?:HC|HORAS.{0,40}CATEDRA)/'],
                ['Proceso/Meses', 10, '/PROCESO|\(MESES\)/'],
                ['Competencia General', 5, '/COMPETENCIA GENERAL/'],
                ['Competencia Específica', 5, '/COMPETENCIA ESPECIFICA/'],
            ],
            'EXCEL_AVANZADO' => [
                ['Título Microsoft Excel', 25, '/PROYECTO ANUAL\s*-\s*MICROSOFT EXCEL/'],
                ['Unidad, Capacidades y Temas', 20, '/UNIDAD\s+CAPACIDADES\s+TEMAS/'],
                ['Columna Check', 18, '/INDICADORES\s+CHECK/'],
                ['Proceso para desarrollo', 22, '/PROCESO PARA.{0,150}EL DESARROLLO/'],
                ['Procedimientos', 8, '/PROCEDIMIENTOS/'],
                ['Instrumentos', 8, '/INSTRUMENTOS/'],
                ['Códigos de tema', 10, '/TEMA\s+\d+\.\d+/'],
                ['Códigos de indicador', 10, '/\b\d+\.\d+\.\d+\b/'],
            ],
            'COMPETENCIA_CONTENIDO' => [
                ['Competencia y Capacidad', 20, '/COMPETENCIA\s+CAPACIDAD/'],
                ['Contenidos', 15, '/INDICADORES\s+CONTENIDOS/'],
                ['Área Transversal', 15, '/AREA TRANSVERSAL/'],
                ['Metodología de Enseñanza', 20, '/METODOLOGIA.{0,100}DE ENSENANZA/'],
                ['Medios de Verificación', 20, '/MEDIOS.{0,100}VERIFICACION/'],
                ['Fecha', 5, '/\bFECHA\b/'],
                ['Días de clase', 5, '/DIAS DE CLASE/'],
            ],
        ];
        $scores = [];
        foreach ($rules as $format => $formatRules) {
            $score = 0;
            $signals = [];
            foreach ($formatRules as [$name, $weight, $pattern]) {
                $matched = preg_match($pattern, $key) === 1;
                if ($matched) $score += $weight;
                $signals[] = ['signal' => $name, 'weight' => $weight, 'matched' => $matched];
            }
            $scores[$format] = ['score' => $score, 'signals' => $signals];
        }
        uasort($scores, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);
        $formats = array_keys($scores);
        $best = $formats[0];
        $second = $formats[1];
        $recognized = $scores[$best]['score'] >= self::MIN_SCORE
            && ($scores[$best]['score'] - $scores[$second]['score']) >= self::MIN_MARGIN;

        return [
            'format' => $recognized ? $best : null,
            'threshold' => self::MIN_SCORE,
            'minimum_margin' => self::MIN_MARGIN,
            'winning_score' => $scores[$best]['score'],
            'margin' => $scores[$best]['score'] - $scores[$second]['score'],
            'scores' => $scores,
        ];
    }
}

<?php
declare(strict_types=1);

/** Reglas compartidas por el preview PDF, la edición y la publicación. */
final class PlanProgrammingRules
{
    public static function inspect(array $rows, array $topicKeys, int $year): array
    {
        $result = ['fechas_invalidas'=>0, 'solapamientos'=>0, 'duplicados'=>0, 'temas_ajenos'=>0, 'temas_validos'=>[], 'errores'=>[]];
        $allowed = array_fill_keys(array_map('strval', $topicKeys), true);
        $seen = []; $valid = [];
        foreach ($rows as $row) {
            $topic = (string)($row['topic_key'] ?? '');
            if (!isset($allowed[$topic])) { $result['temas_ajenos']++; continue; }
            $start = $row['fecha_inicio'] ?? null; $end = $row['fecha_fin'] ?? null;
            if (!self::validDate($start, $year) || !self::validDate($end, $year) || $start > $end) {
                $result['fechas_invalidas']++; continue;
            }
            $key = $topic.':'.$start.':'.$end;
            if (isset($seen[$key])) { $result['duplicados']++; continue; }
            $seen[$key] = true;
            $valid[] = ['topic_key'=>$topic, 'fecha_inicio'=>$start, 'fecha_fin'=>$end];
            $result['temas_validos'][$topic] = true;
        }
        foreach ($valid as $index=>$row) {
            for ($other=$index+1; $other<count($valid); $other++) {
                $next=$valid[$other];
                if ($row['topic_key'] !== $next['topic_key'] && $row['fecha_inicio'] <= $next['fecha_fin'] && $row['fecha_fin'] >= $next['fecha_inicio']) $result['solapamientos']++;
            }
        }
        foreach ([
            'temas_ajenos'=>'La programación contiene un tema que no pertenece al plan.',
            'fechas_invalidas'=>'Seleccione inicio y fin válidos del año del plan; el inicio no puede ser posterior al fin.',
            'duplicados'=>'No se puede repetir el mismo período para un tema.',
            'solapamientos'=>'El período se superpone con otro tema del mismo plan. Corrija las fechas.',
        ] as $key=>$message) if ($result[$key]>0) $result['errores'][]=$message;
        return $result;
    }

    private static function validDate(mixed $value, int $year): bool
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) return false;
        $date=DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d')===$value && (int)$date->format('Y')===$year;
    }
}

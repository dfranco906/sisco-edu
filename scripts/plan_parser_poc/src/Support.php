<?php
declare(strict_types=1);

namespace SiscoEdu\PlanParserPoc;

final class Text
{
    public static function clean(?string $value): string
    {
        $value = str_replace(["\u{00A0}", "\t"], ' ', (string) $value);
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    public static function key(?string $value): string
    {
        $value = mb_strtoupper(self::clean($value), 'UTF-8');
        $value = strtr($value, [
            'Á'=>'A','À'=>'A','Â'=>'A','Ä'=>'A','Ã'=>'A',
            'É'=>'E','È'=>'E','Ê'=>'E','Ë'=>'E',
            'Í'=>'I','Ì'=>'I','Î'=>'I','Ï'=>'I',
            'Ó'=>'O','Ò'=>'O','Ô'=>'O','Ö'=>'O','Õ'=>'O',
            'Ú'=>'U','Ù'=>'U','Û'=>'U','Ü'=>'U','Ñ'=>'N',
        ]);
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        return $ascii === false ? $value : $ascii;
    }

    public static function slice(string $value, int $start, ?int $length = null): string
    {
        if ($start >= mb_strlen($value, 'UTF-8')) return '';
        return $length === null
            ? mb_substr($value, $start, null, 'UTF-8')
            : mb_substr($value, $start, max(0, $length), 'UTF-8');
    }

    public static function position(string $haystack, string $needle): ?int
    {
        $position = mb_stripos($haystack, $needle, 0, 'UTF-8');
        return $position === false ? null : $position;
    }

    /** @param array<int,array<string,mixed>|string> $fragments */
    public static function join(array $fragments, bool $unique = false): ?string
    {
        $parts = [];
        foreach ($fragments as $fragment) {
            $text = self::clean(is_array($fragment) ? (string) ($fragment['text'] ?? '') : $fragment);
            if ($text === '') continue;
            if ($unique && in_array(self::key($text), array_map([self::class, 'key'], $parts), true)) continue;
            $parts[] = $text;
        }
        return $parts ? implode(' ', $parts) : null;
    }

    /** @return array<int,string> */
    public static function splitList(?string $text): array
    {
        $text = self::clean($text);
        if ($text === '') return [];
        $parts = preg_split('/\s*(?:\/|;|,(?=\s*[\p{L}])|\s+y\s+)\s*/ui', $text) ?: [];
        return array_values(array_filter(array_map([self::class, 'clean'], $parts), static fn(string $x): bool => $x !== ''));
    }

    public static function romanToInt(string $roman): ?int
    {
        $roman = strtoupper(trim($roman));
        if ($roman === '' || preg_match('/^[IVXLCDM]+$/', $roman) !== 1) return null;
        $map = ['I'=>1,'V'=>5,'X'=>10,'L'=>50,'C'=>100,'D'=>500,'M'=>1000];
        $total = 0;
        $previous = 0;
        for ($i = strlen($roman) - 1; $i >= 0; $i--) {
            $value = $map[$roman[$i]];
            $total += $value < $previous ? -$value : $value;
            $previous = max($previous, $value);
        }
        return $total;
    }

    /** @return array<string,mixed> */
    public static function warning(string $type, string $message, ?array $row = null, ?string $text = null): array
    {
        return [
            'type' => $type,
            'page' => $row['page'] ?? null,
            'line' => $row['line'] ?? null,
            'text' => $text ?? ($row['text'] ?? null),
            'message' => $message,
        ];
    }
}

final class CanonicalPlan
{
    /** @return array<string,mixed> */
    public static function create(array $document, string $format): array
    {
        return [
            'schema_version' => '1.0-poc',
            'source' => [
                'filename' => $document['filename'],
                'format' => $format,
                'sha256' => $document['sha256'],
                'pages' => $document['pages'],
                'text_layer' => true,
                'extractor' => $document['extractor'],
            ],
            'metadata' => [
                'institucion' => null,
                'materia' => null,
                'profesor' => null,
                'curso' => null,
                'turno' => null,
                'anio' => null,
                'dias_clase' => null,
                'competencia_general' => null,
                'competencia_especifica' => null,
            ],
            'unidades' => [],
            'warnings' => [],
            'confidence' => [],
        ];
    }

    /** @return array<string,mixed> */
    public static function unit(int $order, ?string $code, string $name): array
    {
        return [
            'orden' => $order,
            'codigo' => $code,
            'nombre' => $name,
            'descripcion' => null,
            'horas_catedra' => null,
            'tiempo_texto' => null,
            'proceso_texto' => null,
            'area_transversal' => null,
            'metodologia' => null,
            'medios_verificacion' => null,
            'capacidades' => [],
        ];
    }

    /** @return array<string,mixed> */
    public static function capacity(int $order, string $description): array
    {
        return [
            'orden' => $order,
            'descripcion' => $description,
            'proceso_desarrollo' => null,
            'temas' => [],
        ];
    }

    /** @return array<string,mixed> */
    public static function topic(int $order, ?string $code, string $title): array
    {
        return [
            'orden' => $order,
            'codigo' => $code,
            'titulo' => $title,
            'contenido' => null,
            'horas_catedra' => null,
            'tiempo_texto' => null,
            'fecha_texto' => null,
            'indicadores' => [],
            'procedimientos_evaluativos' => [],
            'instrumentos_evaluativos' => [],
        ];
    }

    /** @return array<string,mixed> */
    public static function indicator(int $order, ?string $code, string $description, ?bool $check = null): array
    {
        return [
            'orden' => $order,
            'codigo' => $code,
            'descripcion' => $description,
            'check' => $check,
        ];
    }
}

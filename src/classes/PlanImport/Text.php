<?php
declare(strict_types=1);

namespace SiscoEdu\PlanImport;

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
            'Á'=>'A','À'=>'A','Â'=>'A','Ä'=>'A','Ã'=>'A','É'=>'E','È'=>'E','Ê'=>'E','Ë'=>'E',
            'Í'=>'I','Ì'=>'I','Î'=>'I','Ï'=>'I','Ó'=>'O','Ò'=>'O','Ô'=>'O','Ö'=>'O','Õ'=>'O',
            'Ú'=>'U','Ù'=>'U','Û'=>'U','Ü'=>'U','Ñ'=>'N',
        ]);
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        return $ascii === false ? $value : $ascii;
    }

    public static function slice(string $value, int $start, ?int $length = null): string
    {
        if ($start >= mb_strlen($value, 'UTF-8')) return '';
        return $length === null ? mb_substr($value, $start, null, 'UTF-8') : mb_substr($value, $start, max(0, $length), 'UTF-8');
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
        $keys = [];
        foreach ($fragments as $fragment) {
            $text = self::clean(is_array($fragment) ? (string) ($fragment['text'] ?? '') : $fragment);
            if ($text === '') continue;
            $key = self::key($text);
            if ($unique && isset($keys[$key])) continue;
            $keys[$key] = true;
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
        return array_values(array_filter(array_map([self::class, 'clean'], $parts), static fn(string $part): bool => $part !== ''));
    }

    public static function romanToInt(string $roman): ?int
    {
        $roman = strtoupper(trim($roman));
        if ($roman === '' || preg_match('/^[IVXLCDM]+$/', $roman) !== 1) return null;
        $map = ['I'=>1,'V'=>5,'X'=>10,'L'=>50,'C'=>100,'D'=>500,'M'=>1000];
        $total = 0; $previous = 0;
        for ($index = strlen($roman) - 1; $index >= 0; $index--) {
            $value = $map[$roman[$index]];
            $total += $value < $previous ? -$value : $value;
            $previous = max($previous, $value);
        }
        return $total;
    }

    /** @param array<string,mixed>|null $row @return array<string,mixed> */
    public static function warning(string $type, string $message, ?array $row = null, ?string $text = null): array
    {
        return CanonicalPlan::warning($type, $message, $row['page'] ?? null, $row['line'] ?? null, $text ?? ($row['text'] ?? null));
    }
}

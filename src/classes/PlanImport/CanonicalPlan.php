<?php
declare(strict_types=1);

namespace SiscoEdu\PlanImport;

final class CanonicalPlan
{
    public const SCHEMA_VERSION = '1.0';
    public const FORMATS = ['ADMINISTRACION_FINANCIERA', 'EXCEL_AVANZADO', 'COMPETENCIA_CONTENIDO'];

    /** @param array<string,mixed> $document @return array<string,mixed> */
    public static function create(array $document, string $format): array
    {
        if (!in_array($format, self::FORMATS, true)) {
            throw new PlanImportException('Formato canónico no permitido.', 'INVALID_CANONICAL_FORMAT');
        }
        return [
            'schema_version' => self::SCHEMA_VERSION,
            'source' => [
                'filename' => (string) ($document['filename'] ?? ''),
                'format' => $format,
                'sha256' => (string) ($document['sha256'] ?? ''),
                'pages' => (int) ($document['pages'] ?? 0),
                'text_layer' => true,
                'extractor' => [
                    'name' => (string) ($document['extractor']['name'] ?? 'pdftotext'),
                    'mode' => (string) ($document['extractor']['mode'] ?? 'table'),
                    'version' => self::nullableString($document['extractor']['version'] ?? null),
                    'elapsed_ms' => isset($document['extractor']['elapsed_ms']) ? (float) $document['extractor']['elapsed_ms'] : null,
                ],
            ],
            'metadata' => self::emptyMetadata(),
            'unidades' => [],
            'warnings' => [],
            'confidence' => self::emptyConfidence(),
        ];
    }

    /** @return array<string,mixed> */
    public static function emptyForTest(): array
    {
        return self::create([
            'filename'=>'fixture.pdf',
            'sha256'=>str_repeat('0', 64),
            'pages'=>1,
            'extractor'=>['name'=>'pdftotext','mode'=>'table','version'=>'4.00','elapsed_ms'=>0],
        ], self::FORMATS[0]);
    }

    /** @return array<string,mixed> */
    public static function unit(int $order, ?string $code, string $name): array
    {
        return [
            'orden'=>$order, 'codigo'=>$code, 'nombre'=>$name, 'descripcion'=>null,
            'horas_catedra'=>null, 'tiempo_texto'=>null, 'proceso_texto'=>null,
            'area_transversal'=>null, 'metodologia'=>null, 'medios_verificacion'=>null,
            'capacidades'=>[],
        ];
    }

    /** @return array<string,mixed> */
    public static function capacity(int $order, string $description): array
    {
        return ['orden'=>$order, 'descripcion'=>$description, 'proceso_desarrollo'=>null, 'temas'=>[]];
    }

    /** @return array<string,mixed> */
    public static function topic(int $order, ?string $code, string $title): array
    {
        return [
            'orden'=>$order, 'codigo'=>$code, 'titulo'=>$title, 'contenido'=>null,
            'horas_catedra'=>null, 'tiempo_texto'=>null, 'fecha_texto'=>null,
            'indicadores'=>[], 'procedimientos_evaluativos'=>[], 'instrumentos_evaluativos'=>[],
        ];
    }

    /** @return array<string,mixed> */
    public static function indicator(int $order, ?string $code, string $description, ?bool $check = null): array
    {
        return ['orden'=>$order, 'codigo'=>$code, 'descripcion'=>$description, 'check'=>$check];
    }

    /** @return array<string,mixed> */
    public static function warning(string $type, string $message, ?int $page = null, ?int $line = null, ?string $text = null): array
    {
        return ['type'=>$type, 'page'=>$page, 'line'=>$line, 'text'=>$text, 'message'=>$message];
    }

    /** @param array<string,mixed> $plan */
    public static function assertValid(array $plan): void
    {
        $errors = self::validationErrors($plan);
        if ($errors) {
            throw new PlanImportException(
                'El modelo canónico no cumple schema 1.0: '.implode('; ', $errors),
                'INVALID_CANONICAL_SCHEMA',
                6,
                422,
                ['errors'=>$errors]
            );
        }
    }

    /** @param array<string,mixed> $plan @return array<int,string> */
    public static function validationErrors(array $plan): array
    {
        $errors = [];
        self::exactKeys($plan, ['schema_version','source','metadata','unidades','warnings','confidence'], '$', $errors, ['debug']);
        if (($plan['schema_version'] ?? null) !== self::SCHEMA_VERSION) $errors[] = '$.schema_version debe ser 1.0';
        self::validateSource($plan['source'] ?? null, $errors);
        self::validateMetadata($plan['metadata'] ?? null, $errors);
        if (!is_array($plan['unidades'] ?? null)) $errors[] = '$.unidades debe ser array';
        else foreach ($plan['unidades'] as $index => $unit) self::validateUnit($unit, '$.unidades['.$index.']', $errors);
        if (!is_array($plan['warnings'] ?? null)) $errors[] = '$.warnings debe ser array';
        else foreach ($plan['warnings'] as $index => $warning) self::validateWarning($warning, '$.warnings['.$index.']', $errors);
        self::validateConfidence($plan['confidence'] ?? null, $errors);
        if (isset($plan['debug']) && !is_array($plan['debug'])) $errors[] = '$.debug debe ser objeto';
        return $errors;
    }

    /** @param mixed $source @param array<int,string> $errors */
    private static function validateSource(mixed $source, array &$errors): void
    {
        if (!is_array($source)) { $errors[] = '$.source debe ser objeto'; return; }
        self::exactKeys($source, ['filename','format','sha256','pages','text_layer','extractor'], '$.source', $errors);
        if (!is_string($source['filename'] ?? null) || trim($source['filename']) === '') $errors[] = '$.source.filename es obligatorio';
        if (!in_array($source['format'] ?? null, self::FORMATS, true)) $errors[] = '$.source.format no permitido';
        if (!is_string($source['sha256'] ?? null) || preg_match('/^[a-f0-9]{64}$/', $source['sha256']) !== 1) $errors[] = '$.source.sha256 inválido';
        if (!is_int($source['pages'] ?? null) || $source['pages'] < 1) $errors[] = '$.source.pages inválido';
        if (($source['text_layer'] ?? null) !== true) $errors[] = '$.source.text_layer debe ser true';
        $extractor = $source['extractor'] ?? null;
        if (!is_array($extractor)) { $errors[] = '$.source.extractor debe ser objeto'; return; }
        self::exactKeys($extractor, ['name','mode','version','elapsed_ms'], '$.source.extractor', $errors);
        if (($extractor['name'] ?? null) !== 'pdftotext') $errors[] = '$.source.extractor.name inválido';
        if (($extractor['mode'] ?? null) !== 'table') $errors[] = '$.source.extractor.mode inválido';
        self::nullableText($extractor['version'] ?? null, '$.source.extractor.version', $errors);
        $elapsed = $extractor['elapsed_ms'] ?? null;
        if ($elapsed !== null && (!is_int($elapsed) && !is_float($elapsed) || $elapsed < 0)) $errors[] = '$.source.extractor.elapsed_ms inválido';
    }

    /** @param mixed $metadata @param array<int,string> $errors */
    private static function validateMetadata(mixed $metadata, array &$errors): void
    {
        $keys = ['institucion','materia','profesor','curso','turno','anio','dias_clase','competencia_general','competencia_especifica'];
        if (!is_array($metadata)) { $errors[] = '$.metadata debe ser objeto'; return; }
        self::exactKeys($metadata, $keys, '$.metadata', $errors);
        foreach ($keys as $key) {
            if ($key === 'anio') continue;
            self::nullableText($metadata[$key] ?? null, '$.metadata.'.$key, $errors);
        }
        $year = $metadata['anio'] ?? null;
        if ($year !== null && (!is_int($year) || $year < 2000 || $year > 2100)) $errors[] = '$.metadata.anio inválido';
    }

    /** @param mixed $unit @param array<int,string> $errors */
    private static function validateUnit(mixed $unit, string $path, array &$errors): void
    {
        $keys = ['orden','codigo','nombre','descripcion','horas_catedra','tiempo_texto','proceso_texto','area_transversal','metodologia','medios_verificacion','capacidades'];
        if (!is_array($unit)) { $errors[] = $path.' debe ser objeto'; return; }
        self::exactKeys($unit, $keys, $path, $errors);
        self::positiveOrder($unit['orden'] ?? null, $path.'.orden', $errors);
        self::nullableText($unit['codigo'] ?? null, $path.'.codigo', $errors);
        self::requiredText($unit['nombre'] ?? null, $path.'.nombre', $errors);
        foreach (['descripcion','tiempo_texto','proceso_texto','area_transversal','metodologia','medios_verificacion'] as $key) self::nullableText($unit[$key] ?? null, $path.'.'.$key, $errors);
        self::nullableNumber($unit['horas_catedra'] ?? null, $path.'.horas_catedra', $errors);
        if (!is_array($unit['capacidades'] ?? null)) $errors[] = $path.'.capacidades debe ser array';
        else foreach ($unit['capacidades'] as $index => $capacity) self::validateCapacity($capacity, $path.'.capacidades['.$index.']', $errors);
    }

    /** @param mixed $capacity @param array<int,string> $errors */
    private static function validateCapacity(mixed $capacity, string $path, array &$errors): void
    {
        if (!is_array($capacity)) { $errors[] = $path.' debe ser objeto'; return; }
        self::exactKeys($capacity, ['orden','descripcion','proceso_desarrollo','temas'], $path, $errors);
        self::positiveOrder($capacity['orden'] ?? null, $path.'.orden', $errors);
        self::requiredText($capacity['descripcion'] ?? null, $path.'.descripcion', $errors);
        self::nullableText($capacity['proceso_desarrollo'] ?? null, $path.'.proceso_desarrollo', $errors);
        if (!is_array($capacity['temas'] ?? null)) $errors[] = $path.'.temas debe ser array';
        else foreach ($capacity['temas'] as $index => $topic) self::validateTopic($topic, $path.'.temas['.$index.']', $errors);
    }

    /** @param mixed $topic @param array<int,string> $errors */
    private static function validateTopic(mixed $topic, string $path, array &$errors): void
    {
        $keys = ['orden','codigo','titulo','contenido','horas_catedra','tiempo_texto','fecha_texto','indicadores','procedimientos_evaluativos','instrumentos_evaluativos'];
        if (!is_array($topic)) { $errors[] = $path.' debe ser objeto'; return; }
        self::exactKeys($topic, $keys, $path, $errors);
        self::positiveOrder($topic['orden'] ?? null, $path.'.orden', $errors);
        self::nullableText($topic['codigo'] ?? null, $path.'.codigo', $errors);
        self::requiredText($topic['titulo'] ?? null, $path.'.titulo', $errors);
        foreach (['contenido','tiempo_texto','fecha_texto'] as $key) self::nullableText($topic[$key] ?? null, $path.'.'.$key, $errors);
        self::nullableNumber($topic['horas_catedra'] ?? null, $path.'.horas_catedra', $errors);
        if (!is_array($topic['indicadores'] ?? null)) $errors[] = $path.'.indicadores debe ser array';
        else foreach ($topic['indicadores'] as $index => $indicator) self::validateIndicator($indicator, $path.'.indicadores['.$index.']', $errors);
        foreach (['procedimientos_evaluativos','instrumentos_evaluativos'] as $key) {
            if (!is_array($topic[$key] ?? null)) { $errors[] = $path.'.'.$key.' debe ser array'; continue; }
            foreach ($topic[$key] as $index => $value) self::requiredText($value, $path.'.'.$key.'['.$index.']', $errors);
        }
    }

    /** @param mixed $indicator @param array<int,string> $errors */
    private static function validateIndicator(mixed $indicator, string $path, array &$errors): void
    {
        if (!is_array($indicator)) { $errors[] = $path.' debe ser objeto'; return; }
        self::exactKeys($indicator, ['orden','codigo','descripcion','check'], $path, $errors);
        self::positiveOrder($indicator['orden'] ?? null, $path.'.orden', $errors);
        self::nullableText($indicator['codigo'] ?? null, $path.'.codigo', $errors);
        self::requiredText($indicator['descripcion'] ?? null, $path.'.descripcion', $errors);
        if (($indicator['check'] ?? null) !== null && !is_bool($indicator['check'])) $errors[] = $path.'.check inválido';
    }

    /** @param mixed $warning @param array<int,string> $errors */
    private static function validateWarning(mixed $warning, string $path, array &$errors): void
    {
        if (!is_array($warning)) { $errors[] = $path.' debe ser objeto'; return; }
        self::exactKeys($warning, ['type','page','line','text','message'], $path, $errors);
        self::requiredText($warning['type'] ?? null, $path.'.type', $errors);
        self::requiredText($warning['message'] ?? null, $path.'.message', $errors);
        foreach (['page','line'] as $key) if (($warning[$key] ?? null) !== null && (!is_int($warning[$key]) || $warning[$key] < 1)) $errors[] = $path.'.'.$key.' inválido';
        self::nullableText($warning['text'] ?? null, $path.'.text', $errors);
    }

    /** @param mixed $confidence @param array<int,string> $errors */
    private static function validateConfidence(mixed $confidence, array &$errors): void
    {
        if (!is_array($confidence)) { $errors[] = '$.confidence debe ser objeto'; return; }
        self::exactKeys($confidence, ['scale','metadata','estructura','asociaciones','global','criteria'], '$.confidence', $errors);
        if (($confidence['scale'] ?? null) !== 'rule_completeness_0_100') $errors[] = '$.confidence.scale inválido';
        foreach (['metadata','estructura','asociaciones','global'] as $key) {
            $value = $confidence[$key] ?? null;
            if (!is_int($value) || $value < 0 || $value > 100) $errors[] = '$.confidence.'.$key.' inválido';
        }
        if (!is_array($confidence['criteria'] ?? null)) $errors[] = '$.confidence.criteria debe ser objeto';
    }

    /** @param array<string,mixed> $value @param array<int,string> $required @param array<int,string> $errors @param array<int,string> $optional */
    private static function exactKeys(array $value, array $required, string $path, array &$errors, array $optional = []): void
    {
        foreach ($required as $key) if (!array_key_exists($key, $value)) $errors[] = $path.'.'.$key.' es obligatorio';
        foreach (array_keys($value) as $key) if (!in_array($key, array_merge($required, $optional), true)) $errors[] = $path.'.'.$key.' no está permitido';
    }

    /** @param array<int,string> $errors */
    private static function requiredText(mixed $value, string $path, array &$errors): void
    {
        if (!is_string($value) || trim($value) === '') $errors[] = $path.' es obligatorio';
    }

    /** @param array<int,string> $errors */
    private static function nullableText(mixed $value, string $path, array &$errors): void
    {
        if ($value !== null && !is_string($value)) $errors[] = $path.' debe ser texto o null';
    }

    /** @param array<int,string> $errors */
    private static function nullableNumber(mixed $value, string $path, array &$errors): void
    {
        if ($value !== null && (!is_int($value) && !is_float($value) || $value < 0)) $errors[] = $path.' debe ser número no negativo o null';
    }

    /** @param array<int,string> $errors */
    private static function positiveOrder(mixed $value, string $path, array &$errors): void
    {
        if (!is_int($value) || $value < 1) $errors[] = $path.' debe ser entero positivo';
    }

    /** @return array<string,mixed> */
    private static function emptyMetadata(): array
    {
        return [
            'institucion'=>null, 'materia'=>null, 'profesor'=>null, 'curso'=>null,
            'turno'=>null, 'anio'=>null, 'dias_clase'=>null,
            'competencia_general'=>null, 'competencia_especifica'=>null,
        ];
    }

    /** @return array<string,mixed> */
    private static function emptyConfidence(): array
    {
        return [
            'scale'=>'rule_completeness_0_100', 'metadata'=>0, 'estructura'=>0,
            'asociaciones'=>0, 'global'=>0, 'criteria'=>[],
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) return null;
        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }
}

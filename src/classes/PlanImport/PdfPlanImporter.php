<?php
declare(strict_types=1);

namespace SiscoEdu\PlanImport;

use SiscoEdu\PlanImport\Contracts\PlanImporterInterface;
use SiscoEdu\PlanImport\Contracts\PdfTextExtractorInterface;
use SiscoEdu\PlanImport\Pdf\{XpdfTableExtractor, PlanFormatDetector, ParserRegistry};
use SiscoEdu\PlanImport\Pdf\Parser\{AdministracionFinancieraParser, ExcelAvanzadoParser, CompetenciaContenidoParser};

final class PdfPlanImporter implements PlanImporterInterface
{
    public function __construct(private readonly PdfTextExtractorInterface $extractor) {}

    public static function fromConfig(array $config): self
    {
        return new self(new XpdfTableExtractor($config));
    }

    public function import(string $sourcePath, bool $debug = false): array
    {
        return $this->importNamed($sourcePath, null, $debug);
    }

    // El nombre original sirve sólo para metadata; nunca se pasa al proceso.
    public function importNamed(string $sourcePath, ?string $originalName, bool $debug = false): array
    {
        $document = $this->extractor->extract($sourcePath);
        if ($originalName !== null) $document['filename'] = basename(str_replace('\\', '/', $originalName));
        $detection = (new PlanFormatDetector())->detect($document['text']);
        if ($detection['format'] === null) {
            throw new PlanImportException("No pudimos reconocer automáticamente la estructura de este plan.\n\nPuede cargarlo manualmente o utilizar uno de los formatos compatibles.", 'UNKNOWN_FORMAT', 5);
        }
        $registry = new ParserRegistry([new AdministracionFinancieraParser(), new ExcelAvanzadoParser(), new CompetenciaContenidoParser()]);
        $plan = $registry->get($detection['format'])->parse($document);
        $filename = Text::key($plan['source']['filename']);
        $course = Text::key((string) $plan['metadata']['curso']);
        $expected = str_contains($filename, 'SEGUNDO CURSO') ? 2 : (str_contains($filename, 'TERCER CURSO') ? 3 : null);
        if ($expected !== null && preg_match('/^\s*([123])/', $course, $match) && (int)$match[1] !== $expected) {
            $plan['warnings'][] = Text::warning('SOURCE_METADATA_MISMATCH', "El nombre del archivo sugiere curso {$expected}, pero el PDF declara {$plan['metadata']['curso']}.", null, $plan['source']['filename']);
        }
        $plan = (new PlanValidator())->validate($plan);
        $plan['confidence'] = (new ConfidenceCalculator())->calculate($plan);
        if ($debug) {
            $plan['debug']['format_detection'] = $detection;
            $plan['debug']['counts'] = self::counts($plan);
        } else unset($plan['debug']);
        CanonicalPlan::assertValid($plan);
        return $plan;
    }

    public static function counts(array $plan): array
    {
        $counts = ['unidades'=>count($plan['unidades']), 'capacidades'=>0, 'temas'=>0, 'indicadores'=>0];
        foreach ($plan['unidades'] as $unit) foreach ($unit['capacidades'] as $capacity) {
            $counts['capacidades']++;
            foreach ($capacity['temas'] as $topic) {
                $counts['temas']++;
                $counts['indicadores'] += count($topic['indicadores']);
            }
        }
        return $counts;
    }
}

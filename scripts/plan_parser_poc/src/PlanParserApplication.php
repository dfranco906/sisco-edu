<?php
declare(strict_types=1);

namespace SiscoEdu\PlanParserPoc;

final class PlanParserApplication
{
    /** @var array<string,PlanPdfParserInterface> */
    private array $parsers = [];

    /** @param array<int,PlanPdfParserInterface> $parsers */
    public function __construct(
        private readonly PdfTextExtractorInterface $extractor,
        private readonly PlanFormatDetector $detector,
        array $parsers,
        private readonly PlanValidator $validator,
        private readonly ConfidenceCalculator $confidence
    ) {
        foreach ($parsers as $parser) $this->parsers[$parser->format()] = $parser;
    }

    /** @return array<string,mixed> */
    public function parse(string $pdfPath, bool $debug = false): array
    {
        $document = $this->extractor->extract($pdfPath);
        $detection = $this->detector->detect((string) $document['text']);
        $format = $detection['format'];
        if ($format === null || !isset($this->parsers[$format])) {
            throw new PlanParserException(
                'El formato no alcanzó el umbral o margen determinista requerido.',
                'UNKNOWN_FORMAT',
                5
            );
        }
        $plan = $this->parsers[$format]->parse($document);
        $this->addFilenameMetadataWarning($plan);
        $plan = $this->validator->validate($plan);
        $plan['confidence'] = $this->confidence->calculate($plan);
        $counts = $this->counts($plan);
        if ($debug) {
            $plan['debug']['format_detection'] = $detection;
            $plan['debug']['counts'] = $counts;
            $plan['debug']['pages'] = $document['pages'];
            $plan['debug']['warning_count'] = count($plan['warnings']);
        } else {
            unset($plan['debug']);
        }
        return $plan;
    }

    /** @param array<string,mixed> $plan */
    private function addFilenameMetadataWarning(array &$plan): void
    {
        $filename = Text::key((string) $plan['source']['filename']);
        $course = Text::key((string) ($plan['metadata']['curso'] ?? ''));
        $expected = null;
        if (str_contains($filename, 'SEGUNDO CURSO')) $expected = 2;
        elseif (str_contains($filename, 'TERCER CURSO')) $expected = 3;
        if ($expected !== null && preg_match('/^\s*([123])/', $course, $match) === 1 && (int) $match[1] !== $expected) {
            $plan['warnings'][] = Text::warning(
                'SOURCE_METADATA_MISMATCH',
                "El nombre del archivo sugiere curso {$expected}, pero el PDF declara {$plan['metadata']['curso']}.",
                null,
                (string) $plan['source']['filename']
            );
        }
    }

    /** @param array<string,mixed> $plan @return array<string,int> */
    public function counts(array $plan): array
    {
        $counts = ['unidades'=>0,'capacidades'=>0,'temas'=>0,'indicadores'=>0];
        foreach ($plan['unidades'] as $unit) {
            $counts['unidades']++;
            foreach ($unit['capacidades'] as $capacity) {
                $counts['capacidades']++;
                foreach ($capacity['temas'] as $topic) {
                    $counts['temas']++;
                    $counts['indicadores'] += count($topic['indicadores']);
                }
            }
        }
        return $counts;
    }
}

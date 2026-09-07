<?php
declare(strict_types=1);

namespace SiscoEdu\PlanParserPoc;

interface PdfTextExtractorInterface
{
    /** @return array<string,mixed> */
    public function extract(string $pdfPath): array;
}

interface PlanPdfParserInterface
{
    public function format(): string;

    /** @param array<string,mixed> $document @return array<string,mixed> */
    public function parse(array $document): array;
}

final class PlanParserException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $warningType = 'PARSER_ERROR',
        public readonly int $exitCode = 1
    ) {
        parent::__construct($message);
    }
}

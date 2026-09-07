<?php
declare(strict_types=1);

namespace SiscoEdu\PlanImport\Contracts;

interface PdfTextExtractorInterface
{
    /** @return array<string,mixed> */
    public function extract(string $pdfPath): array;
}

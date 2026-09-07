<?php
declare(strict_types=1);

namespace SiscoEdu\PlanImport\Contracts;

interface PlanImporterInterface
{
    /** @return array<string,mixed> */
    public function import(string $sourcePath, bool $debug = false): array;
}

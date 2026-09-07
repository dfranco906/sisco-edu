<?php
declare(strict_types=1);

namespace SiscoEdu\PlanImport\Contracts;

interface PlanFormatParserInterface
{
    public function format(): string;

    /** @param array<string,mixed> $document @return array<string,mixed> */
    public function parse(array $document): array;
}

<?php
declare(strict_types=1);

namespace SiscoEdu\PlanImport;

final class PlanImportException extends \RuntimeException
{
    /** @param array<string,mixed> $context */
    public function __construct(
        string $message,
        public readonly string $errorType = 'PLAN_IMPORT_ERROR',
        public readonly int $exitCode = 1,
        public readonly int $httpStatus = 422,
        public readonly array $context = []
    ) {
        parent::__construct($message);
    }
}

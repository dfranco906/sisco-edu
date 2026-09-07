<?php
declare(strict_types=1);

use SiscoEdu\PlanParserPoc\AdministracionFinancieraParser;
use SiscoEdu\PlanParserPoc\CompetenciaContenidoParser;
use SiscoEdu\PlanParserPoc\ConfidenceCalculator;
use SiscoEdu\PlanParserPoc\ExcelAvanzadoParser;
use SiscoEdu\PlanParserPoc\PdfTextExtractorInterface;
use SiscoEdu\PlanParserPoc\PdftotextExtractor;
use SiscoEdu\PlanParserPoc\PlanFormatDetector;
use SiscoEdu\PlanParserPoc\PlanParserApplication;
use SiscoEdu\PlanParserPoc\PlanValidator;

require_once __DIR__.'/src/Contracts.php';
require_once __DIR__.'/src/Support.php';
require_once __DIR__.'/src/PdftotextExtractor.php';
require_once __DIR__.'/src/PlanFormatDetector.php';
require_once __DIR__.'/src/AbstractTableParser.php';
require_once __DIR__.'/src/AdministracionFinancieraParser.php';
require_once __DIR__.'/src/ExcelAvanzadoParser.php';
require_once __DIR__.'/src/CompetenciaContenidoParser.php';
require_once __DIR__.'/src/PlanValidator.php';
require_once __DIR__.'/src/ConfidenceCalculator.php';
require_once __DIR__.'/src/PlanParserApplication.php';

function createPlanParserPoc(?string $pdftotextBinary = null, ?PdfTextExtractorInterface $extractor = null): PlanParserApplication
{
    return new PlanParserApplication(
        $extractor ?? new PdftotextExtractor($pdftotextBinary),
        new PlanFormatDetector(),
        [new AdministracionFinancieraParser(), new ExcelAvanzadoParser(), new CompetenciaContenidoParser()],
        new PlanValidator(),
        new ConfidenceCalculator()
    );
}

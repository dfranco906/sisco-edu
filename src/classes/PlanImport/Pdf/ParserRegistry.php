<?php
declare(strict_types=1);

namespace SiscoEdu\PlanImport\Pdf;

use SiscoEdu\PlanImport\Contracts\PlanFormatParserInterface;
use SiscoEdu\PlanImport\PlanImportException;

final class ParserRegistry
{
    /** @var array<string,PlanFormatParserInterface> */
    private array $parsers = [];

    /** @param array<int,PlanFormatParserInterface> $parsers */
    public function __construct(array $parsers)
    {
        foreach ($parsers as $parser) {
            $format = $parser->format();
            if (isset($this->parsers[$format])) throw new PlanImportException('Parser duplicado para '.$format.'.', 'DUPLICATE_FORMAT_PARSER', 4, 500);
            $this->parsers[$format] = $parser;
        }
    }

    public function get(string $format): PlanFormatParserInterface
    {
        if (!isset($this->parsers[$format])) throw new PlanImportException('No existe adaptador para el formato detectado.', 'FORMAT_PARSER_NOT_FOUND', 5, 422, ['format'=>$format]);
        return $this->parsers[$format];
    }

    /** @return array<int,string> */
    public function formats(): array { return array_keys($this->parsers); }
}

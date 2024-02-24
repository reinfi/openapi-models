<?php

namespace Reinfi\OpenApiModels\OutputFormatter;

use Reinfi\OpenApiModels\Validate\ValidationResult;
use Symfony\Component\Console\Style\SymfonyStyle;

interface OutputFormatterInterface
{
    public function formatOutput(ValidationResult $result, SymfonyStyle $output): int;
}

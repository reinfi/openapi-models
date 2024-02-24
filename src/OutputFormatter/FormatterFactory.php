<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\OutputFormatter;

class FormatterFactory
{
    public function create(?string $type): OutputFormatterInterface
    {
        return match ($type) {
            'junit' => new JUnitFormatter(),
            default => new DefaultFormatter(),
        };
    }
}

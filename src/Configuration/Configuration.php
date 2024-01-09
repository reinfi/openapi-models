<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Configuration;

use DateTimeInterface;

readonly class Configuration
{
    public function __construct(
        /** @var string[] $paths */
        public array $paths,
        public string $outputPath,
        public string $namespace,
        public bool $clearOutputDirectory = false,
        public bool $dateTimeAsObject = false,
        public string $dateFormat = 'Y-m-d',
        public string $dateTimeFormat = DateTimeInterface::RFC3339,
    ) {
    }
}

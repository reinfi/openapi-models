<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Configuration;

readonly class Configuration
{
    public function __construct(
        /** @var string[] $paths */
        public array $paths,
        public string $outputPath,
        public string $namespace,
        public bool $clearOutputDirectory,
        public bool $dateTimeAsObject,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

readonly class ClassReference
{
    public function __construct(
        public OpenApiType $openApiType,
        public string $name
    ) {
    }
}

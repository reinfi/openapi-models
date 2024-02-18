<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Model;

use openapiphp\openapi\spec\Schema;

readonly class ScalarType
{
    public function __construct(
        public string $name,
        public Schema $schema
    ) {
    }
}

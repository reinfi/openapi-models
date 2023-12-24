<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Model;

use cebe\openapi\spec\Schema;

readonly class SchemaWithName
{
    public function __construct(
        public string $name,
        public Schema $schema
    ) {
    }
}

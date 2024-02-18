<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Model;

use openapiphp\openapi\spec\Schema;
use Reinfi\OpenApiModels\Generator\OpenApiType;

readonly class SchemaWithName
{
    public function __construct(
        public OpenApiType $openApiType,
        public string $name,
        public Schema $schema
    ) {
    }
}

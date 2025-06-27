<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Model;

use openapiphp\openapi\spec\Schema;

readonly class InlineSchemaReference
{
    public function __construct(
        public Schema $schema
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Model;

use cebe\openapi\spec\Schema;

readonly class OneOfReference
{
    public function __construct(
        public Schema $schema
    ) {
    }
}

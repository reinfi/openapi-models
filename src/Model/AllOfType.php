<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Model;

use cebe\openapi\spec\Schema;
use Reinfi\OpenApiModels\Generator\Types;

readonly class AllOfType
{
    public function __construct(
        public Types|string $type,
        public Schema $schema
    ) {
    }
}

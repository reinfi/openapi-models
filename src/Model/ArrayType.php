<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Model;

use Reinfi\OpenApiModels\Generator\ClassReference;

readonly class ArrayType
{
    public function __construct(
        public ClassReference|string|OneOfType $type,
        public bool $nullable,
        public string $docType,
        /**
         * @var string[]
         */
        public array $imports = [],
    ) {
    }
}

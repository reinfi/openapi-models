<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Model;

use Nette\PhpGenerator\Parameter;
use Reinfi\OpenApiModels\Serialization\SerializableType;

readonly class ParameterSerializationType
{
    public function __construct(
        public SerializableType $type,
        public Parameter $parameter,
        public bool $required,
    ) {
    }
}

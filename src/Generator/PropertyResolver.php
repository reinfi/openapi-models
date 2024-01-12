<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PromotedParameter;
use Reinfi\OpenApiModels\Model\ScalarType;

readonly class PropertyResolver
{
    public function resolve(
        Method $constructor,
        string $name,
        Schema|Reference $schema,
        bool $required,
        ScalarType|ClassReference|Types|string $type,
    ): PromotedParameter {
        $property = $constructor->addPromotedParameter($name);

        if (is_string($type)) {
            $property->setType($type);
        }

        if ($type instanceof ClassReference || $type instanceof ScalarType) {
            $property->setType($type->name);
        }

        if ($schema->nullable ?? false) {
            $property->setNullable();
        }

        if (! $required) {
            $property->setDefaultValue(null)
                ->setNullable();
        }

        return $property;
    }
}

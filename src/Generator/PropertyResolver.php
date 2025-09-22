<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use Nette\PhpGenerator\Helpers;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PromotedParameter;
use openapiphp\openapi\spec\Reference;
use openapiphp\openapi\spec\Schema;
use Reinfi\OpenApiModels\Model\InlineSchemaReference;
use Reinfi\OpenApiModels\Model\OneOfReference;
use Reinfi\OpenApiModels\Model\ScalarType;

readonly class PropertyResolver
{
    public function resolve(
        Method $constructor,
        string $name,
        Schema|Reference $schema,
        bool $required,
        ScalarType|ClassReference|InlineSchemaReference|OneOfReference|Types|string $type,
    ): PromotedParameter {
        if (! Helpers::isIdentifier($name)) {
            $name = str_replace('-', '_', $name);
        }

        $property = $constructor->addPromotedParameter($name);

        if (is_string($type)) {
            $property->setType($type);
        }

        if ($type instanceof ClassReference || $type instanceof ScalarType) {
            $property->setType($type->name);

            if ($type instanceof ScalarType && $type->schema->nullable) {
                $property->setNullable();
            }
        }

        if ($schema instanceof Schema && ($schema->nullable ?? false)) {
            $property->setNullable();
        }

        if (! $required) {
            $property->setDefaultValue(null)
                ->setNullable();
        }

        if ($type === Types::Null) {
            $property->setType('null');
        }

        return $property;
    }
}

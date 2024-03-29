<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Serialization;

use openapiphp\openapi\spec\OpenApi;
use openapiphp\openapi\spec\Schema;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Exception\InvalidDateFormatException;
use Reinfi\OpenApiModels\Generator\AllOfPropertySchemaResolver;
use Reinfi\OpenApiModels\Generator\TypeResolver;
use Reinfi\OpenApiModels\Generator\Types;

class DateTimeFormatResolver
{
    public function __construct(
        private readonly TypeResolver $typeResolver,
        private readonly AllOfPropertySchemaResolver $allOfPropertySchemaResolver,
    ) {
    }

    public function resolveFormat(
        Configuration $configuration,
        OpenApi $openApi,
        Schema $schema,
        Types|string $type,
        string $parameterName
    ): string {
        if ($type === Types::AllOf) {
            $allOfType = $this->allOfPropertySchemaResolver->resolve($openApi, $schema, $parameterName);

            return $this->resolveFormat(
                $configuration,
                $openApi,
                $allOfType->schema,
                $allOfType->type,
                $parameterName
            );
        }

        if ($type === Types::OneOf) {
            foreach ($schema->oneOf as $oneOfSchema) {
                $type = $this->typeResolver->resolve($openApi, $oneOfSchema);
                if (in_array($type, [Types::DateTime, Types::Date], true)) {
                    break;
                }
            }
        }

        if ($type === Types::Array && $schema->items !== null) {
            $type = $this->typeResolver->resolve($openApi, $schema->items);
        }

        return match ($type) {
            Types::Date => $configuration->dateFormat,
            Types::DateTime => $configuration->dateTimeFormat,
            default => throw new InvalidDateFormatException($parameterName)
        };
    }
}

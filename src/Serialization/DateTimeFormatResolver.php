<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Serialization;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Exception\InvalidDateFormatException;
use Reinfi\OpenApiModels\Generator\TypeResolver;
use Reinfi\OpenApiModels\Generator\Types;

class DateTimeFormatResolver
{
    public function __construct(
        private readonly TypeResolver $typeResolver
    ) {
    }

    public function resolveFormat(
        Configuration $configuration,
        OpenApi $openApi,
        Schema $schema,
        Types|string $type,
        string $parameterName
    ): string {
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

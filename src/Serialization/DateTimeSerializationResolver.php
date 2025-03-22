<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Serialization;

use DateTimeInterface;
use openapiphp\openapi\spec\OpenApi;
use openapiphp\openapi\spec\Reference;
use openapiphp\openapi\spec\Schema;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Exception\PropertyNotFoundException;
use Reinfi\OpenApiModels\Generator\ReferenceResolver;
use Reinfi\OpenApiModels\Generator\TypeResolver;
use Reinfi\OpenApiModels\Generator\Types;
use Reinfi\OpenApiModels\Model\ParameterSerializationType;

class DateTimeSerializationResolver
{
    public function __construct(
        private readonly ReferenceResolver $referenceResolver,
        private readonly TypeResolver $typeResolver,
        private readonly DateTimeFormatResolver $dateTimeFormatResolver,
    ) {
    }

    public function resolve(
        Configuration $configuration,
        OpenApi $openApi,
        Schema $schema,
        ParameterSerializationType $dateTimeParameter,
    ): string {
        $parameter = $dateTimeParameter->parameter;
        $property = $this->findPropertySchema($openApi, $schema, $parameter->getName());

        if ($property === null) {
            throw new PropertyNotFoundException($parameter->getName());
        }

        if ($property instanceof Reference) {
            $property = $this->referenceResolver->resolve($openApi, $property)
                ->schema;
        }

        $type = $this->typeResolver->resolve($openApi, $property);

        $dateTimeFormat = $this->dateTimeFormatResolver->resolveFormat(
            $configuration,
            $openApi,
            $property,
            $type,
            $parameter->getName()
        );

        switch ($type) {
            case Types::Array:
                $arrayMapFunction = sprintf(
                    'array_map(static fn (%2$s $date): string => $date->format(\'%3$s\'), $this->%1$s)',
                    $parameter->getName(),
                    DateTimeInterface::class,
                    $dateTimeFormat
                );
                if ($parameter->isNullable()) {
                    return sprintf(
                        '\'%1$s\' => $this->%1$s === null ? $this->%1$s : %2$s,',
                        $parameter->getName(),
                        $arrayMapFunction
                    );
                }
                return sprintf('\'%1$s\' => %2$s,', $parameter->getName(), $arrayMapFunction);

            case Types::OneOf:
                return sprintf(
                    '\'%1$s\' => $this->%1$s instanceOf %2$s ? $this->%1$s->format(\'%3$s\') : $this->%1$s,',
                    $parameter->getName(),
                    DateTimeInterface::class,
                    $dateTimeFormat
                );
            default:
                return sprintf(
                    '\'%1$s\' => $this->%1$s%2$s->format(\'%3$s\'),',
                    $parameter->getName(),
                    $parameter->isNullable() ? '?' : '',
                    $dateTimeFormat
                );
        }
    }

    private function findPropertySchema(OpenApi $openApi, Schema $schema, string $name): Schema|Reference|null
    {
        $property = $schema->properties[$name] ?? null;

        if ($property !== null) {
            return $property;
        }

        if (is_array($schema->allOf) && count($schema->allOf) > 0) {
            foreach ($schema->allOf as $allOfSchemaOrReference) {
                if ($allOfSchemaOrReference instanceof Reference) {
                    $allOfSchemaOrReference = $this->referenceResolver->resolve(
                        $openApi,
                        $allOfSchemaOrReference
                    )->schema;
                }

                $property = $this->findPropertySchema($openApi, $allOfSchemaOrReference, $name);

                if ($property !== null) {
                    return $property;
                }
            }
        }

        return null;
    }
}

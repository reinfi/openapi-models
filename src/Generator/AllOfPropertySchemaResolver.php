<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use openapiphp\openapi\spec\OpenApi;
use openapiphp\openapi\spec\Reference;
use openapiphp\openapi\spec\Schema;
use Reinfi\OpenApiModels\Exception\InvalidAllOfException;
use Reinfi\OpenApiModels\Model\AllOfType;
use Reinfi\OpenApiModels\Model\OneOfReference;
use Reinfi\OpenApiModels\Model\ScalarType;

class AllOfPropertySchemaResolver
{
    public function __construct(
        private readonly TypeResolver $typeResolver,
        private readonly ReferenceResolver $referenceResolver,
    ) {
    }

    public function resolve(OpenApi $openApi, Schema $schema, string $propertyName): AllOfType
    {
        /** @var AllOfType[] $resolvedTypes */
        $resolvedTypes = [];
        $hasSingleType = false;
        $hasNullType = false;

        foreach ($schema->allOf as $allOfSchema) {
            $type = $this->typeResolver->resolve($openApi, $allOfSchema, false);

            if ($type === null) {
                continue;
            }

            if ($type instanceof OneOfReference || $type === Types::OneOf) {
                throw new InvalidAllOfException(
                    $propertyName,
                    'oneOf is not allowed, because it can not be resolved to combinable types'
                );
            }

            if ($hasNullType) {
                throw new InvalidAllOfException(
                    $propertyName,
                    'additional null type found, use oneOf if you want to set a property nullable'
                );
            }

            if ($hasSingleType) {
                if ($type === Types::Null) {
                    throw new InvalidAllOfException(
                        $propertyName,
                        'additional null type found, use oneOf if you want to set a property nullable'
                    );
                }

                throw new InvalidAllOfException(
                    $propertyName,
                    'found multiple types beside a single type (scalar, enum, date-time), this is not allowed as they can not be combined'
                );
            }

            // @phpstan-ignore-next-line This is an invalid phpstan error.
            if ($allOfSchema instanceof Reference) {
                if ($type instanceof ScalarType) {
                    $hasSingleType = true;
                    $resolvedTypes[] = new AllOfType($type->name, $type->schema);
                    continue;
                }

                $referenceSchema = $this->referenceResolver->resolve($openApi, $allOfSchema);
                $referenceType = $this->typeResolver->resolve($openApi, $referenceSchema->schema, false);

                if ($referenceType === null) {
                    continue;
                }

                if (in_array($referenceType, [Types::AllOf, Types::OneOf, Types::AnyOf])) {
                    throw new InvalidAllOfException(
                        $propertyName,
                        sprintf('found type "%s" which is not allowed', $referenceType->value)
                    );
                }

                $type = $referenceType;
                $allOfSchema = $referenceSchema->schema;
            }

            if ((is_string(
                $type
            ) || $type === Types::Enum || $type === Types::Date || $type === Types::DateTime || $type === Types::Array)) {
                $hasSingleType = true;
                $resolvedTypes[] = new AllOfType($type, $allOfSchema);
            }

            if ($type === Types::Null) {
                $hasNullType = true;
                $resolvedTypes[] = new AllOfType($type, $allOfSchema);
            }

            if ($type === Types::Object) {
                $resolvedTypes[] = new AllOfType($type, $allOfSchema);
            }
        }

        if ($hasNullType) {
            return new AllOfType(
                Types::Null,
                new Schema([
                    'type' => 'null',
                ])
            );
        }

        if (count($resolvedTypes) === 0) {
            throw new InvalidAllOfException($propertyName, 'no types found');
        }

        if (count($resolvedTypes) === 1 && $hasSingleType) {
            return $resolvedTypes[0];
        }

        $properties = array_merge_recursive(
            ...array_map(static fn (AllOfType $allOfType) => $allOfType->schema->properties, $resolvedTypes)
        );

        $required = array_merge_recursive(
            ...array_map(
                static fn (AllOfType $allOfType) => is_array(
                    $allOfType->schema->required
                ) ? $allOfType->schema->required : [],
                $resolvedTypes
            )
        );

        return new AllOfType(
            Types::Object,
            new Schema([
                'type' => 'object',
                'required' => $required,
                'properties' => $properties,
            ])
        );
    }
}

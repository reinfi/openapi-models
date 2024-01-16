<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use DateTimeInterface;
use InvalidArgumentException;
use Reinfi\OpenApiModels\Model\OneOfReference;
use Reinfi\OpenApiModels\Model\ScalarType;

readonly class TypeResolver
{
    public function __construct(
        private ReferenceResolver $referenceResolver,
        private NamespaceResolver $namespaceResolver,
    ) {
    }

    /**
     * @return ($schema is Reference ? ClassReference|OneOfReference|ScalarType : string|Types)
     */
    public function resolve(
        OpenApi $openApi,
        Schema|Reference $schema
    ): ScalarType|ClassReference|OneOfReference|string|Types {
        if ($schema instanceof Reference) {
            $schemaWithName = $this->referenceResolver->resolve($openApi, $schema);

            $referenceType = $this->resolve($openApi, $schemaWithName->schema);

            if (in_array($referenceType, [Types::Date, Types::DateTime])) {
                return new ClassReference($schemaWithName->openApiType, DateTimeInterface::class);
            }

            if ($referenceType === Types::OneOf) {
                return new OneOfReference($schemaWithName->schema);
            }

            if (is_string($referenceType)) {
                return new ScalarType($referenceType, $schemaWithName->schema);
            }

            return new ClassReference(
                $schemaWithName->openApiType,
                $this->namespaceResolver->resolveNamespace($schemaWithName->openApiType)
                    ->resolveName($schemaWithName->name)
            );
        }

        if (is_array($schema->allOf) && count($schema->allOf) > 0) {
            return Types::AllOf;
        }

        if (is_array($schema->oneOf) && count($schema->oneOf) > 0) {
            return Types::OneOf;
        }

        if (is_array($schema->enum) && $this->isValidEnum($schema)) {
            return Types::Enum;
        }

        $type = match ($schema->type) {
            'number' => match ($schema->format) {
                'double', 'float' => 'float',
                default => 'int'
            },
            'integer' => 'int',
            'boolean' => 'bool',
            'string' => match ($schema->format) {
                'date', => Types::Date,
                'date-time' => Types::DateTime,
                default => 'string',
            },
            'array' => Types::Array,
            'object' => Types::Object,
            'null' => Types::Null,
            default => null,
        };

        if ($type === null) {
            if (is_array($schema->properties) && count($schema->properties) > 0) {
                return Types::Object;
            }

            throw new InvalidArgumentException(sprintf('Not implemented type "%s" found', $schema->type));
        }

        return $type;
    }

    private function isValidEnum(Schema $schema): bool
    {
        return in_array($schema->type, ['string', 'number'], true);
    }
}

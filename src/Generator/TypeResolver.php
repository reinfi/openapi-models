<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use InvalidArgumentException;

readonly class TypeResolver
{
    public function __construct(
        private ReferenceResolver $referenceResolver,
        private NamespaceResolver $namespaceResolver,
    ) {
    }

    /**
     * @return ($schema is Reference ? ClassReference : string|Types)
     */
    public function resolve(OpenApi $openApi, Schema|Reference $schema): ClassReference|string|Types
    {
        if ($schema instanceof Reference) {
            $schemaWithName = $this->referenceResolver->resolve($openApi, $schema);

            return new ClassReference(
                $schemaWithName->openApiType,
                $this->namespaceResolver->resolveNamespace($schemaWithName->openApiType)->resolveName(
                    $schemaWithName->name
                )
            );
        }

        if (is_array($schema->oneOf) && count($schema->oneOf) > 0) {
            return Types::OneOf;
        }

        if (is_array($schema->enum) && $this->isValidEnum($schema)) {
            return Types::Enum;
        }

        return match ($schema->type) {
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
            default => throw new InvalidArgumentException(sprintf('Not implemented type "%s" found', $schema->type))
        };
    }

    private function isValidEnum(Schema $schema): bool
    {
        return in_array($schema->type, ['string', 'number'], true);
    }
}

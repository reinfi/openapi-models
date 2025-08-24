<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use DateTimeInterface;
use InvalidArgumentException;
use openapiphp\openapi\spec\OpenApi;
use openapiphp\openapi\spec\Reference;
use openapiphp\openapi\spec\Schema;
use Reinfi\OpenApiModels\Model\InlineSchemaReference;
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
     * @phpstan-assert Schema $schema when return type is string|Types|null
     * @return ($schema is Reference ? ClassReference|InlineSchemaReference|OneOfReference|ScalarType : ($throwException is true ? string|Types : string|Types|null))
     */
    public function resolve(
        OpenApi $openApi,
        Schema|Reference $schema,
        bool $throwException = true
    ): ScalarType|ClassReference|InlineSchemaReference|OneOfReference|string|Types|null {
        if ($schema instanceof Reference) {
            $schemaWithName = $this->referenceResolver->resolve($openApi, $schema);

            $referenceType = $this->resolve($openApi, $schemaWithName->schema);

            if (in_array($referenceType, [Types::Date, Types::DateTime], true)) {
                return new ClassReference($schemaWithName->openApiType, DateTimeInterface::class);
            }

            if ($referenceType === Types::OneOf) {
                return new OneOfReference($schemaWithName->schema);
            }

            if (is_string($referenceType)) {
                return new ScalarType($referenceType, $schemaWithName->schema);
            }

            if ($schemaWithName->name === '') {
                return new InlineSchemaReference($schemaWithName->schema);
            }

            return new ClassReference(
                $schemaWithName->openApiType,
                $this->namespaceResolver->resolveNamespace($schemaWithName->openApiType, $schemaWithName->schema)
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

        $schemaType = $schema->type;
        if (is_array($schema->type) && count($schema->type) == 2 && in_array("null", $schema->type)) {
            $schemaType = current( array_filter($schema->type, fn (string $type) => $type !== 'null'));
        }

        $type = match ($schemaType) {
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

            if ($throwException) {
                throw new InvalidArgumentException(sprintf(
                    'Not implemented type "%s" found',
                    is_array($schema->type) ? join(',', $schema->type) : $schema->type
                ));
            }
        }

        return $type;
    }

    private function isValidEnum(Schema $schema): bool
    {
        return in_array($schema->type, ['string', 'number'], true);
    }
}

<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use InvalidArgumentException;
use Nette\PhpGenerator\PhpNamespace;

readonly class TypeResolver
{
    public function __construct(
        private ReferenceResolver $referenceResolver
    ) {
    }

    public function resolve(OpenApi $openApi, Schema|Reference $schema, PhpNamespace $namespace): string|Types
    {
        if ($schema instanceof Reference) {
            return $namespace->resolveName($this->referenceResolver->resolve($openApi, $schema)->name);
        }

        if (is_array($schema->oneOf)) {
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
            'string' => 'string',
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

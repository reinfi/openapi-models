<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use InvalidArgumentException;
use Nette\PhpGenerator\PhpNamespace;

readonly class TypeTransformer
{
    public function transform(OpenApi $openApi, Schema|Reference $schema, PhpNamespace $namespace): string|Types
    {
        if ($schema instanceof Reference) {
            return $this->resolveReference($openApi, $schema, $namespace);
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

    private function resolveReference(OpenApi $openApi, Reference $reference, PhpNamespace $namespace): string
    {
        if (preg_match(
            '/^(?<fileOrUrl>[^#]+)?#\/components\/schemas\/(?<name>.+)$/',
            $reference->getReference(),
            $matches
        ) !== 1) {
            throw new InvalidArgumentException(
                sprintf('Can not resolve reference "%s"', $reference->getReference())
            );
        }

        $schema = $openApi->components->schemas[$matches['name']] ?? null;

        if ($schema instanceof Schema) {
            return $namespace->resolveName($matches['name']);
        }

        throw new InvalidArgumentException(sprintf('Can not resolve reference "%s"', $reference->getReference()));
    }

    private function isValidEnum(Schema $schema): bool
    {
        return in_array($schema->type, ['string', 'number'], true);
    }
}

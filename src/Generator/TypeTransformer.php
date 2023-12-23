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
    public function transform(OpenApi $openApi, Schema|Reference $schema, PhpNamespace $namespace): string
    {
        if ($schema instanceof Reference) {
            return $this->resolveReference($openApi, $schema, $namespace);
        }

        return match ($schema->type) {
            'number' => $this->transformNumber($schema),
            'integer' => 'int',
            'boolean' => 'bool',
            'string' => 'string',
            'array' => 'array',
            'object' => 'object',
            default => throw new InvalidArgumentException(sprintf('Not implemented type "%s" found', $schema->type))
        };
    }

    private function transformNumber(Schema $schema): string
    {
        return match ($schema->format) {
            'double', 'float' => 'float',
            default => 'int'
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
}

<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use InvalidArgumentException;
use Reinfi\OpenApiModels\Model\SchemaWithName;

readonly class ReferenceResolver
{
    public function resolve(OpenApi $openApi, Reference $reference): SchemaWithName
    {
        if (preg_match(
            '/^(?<fileOrUrl>[^#]+)?#\/components\/schemas\/(?<name>.+)$/',
            $reference->getReference(),
            $matches
        ) !== 1) {
            throw new InvalidArgumentException(
                sprintf('Invalid reference "%s" given, does not match pattern', $reference->getReference())
            );
        }

        $schema = $openApi->components->schemas[$matches['name']] ?? null;

        if ($schema instanceof Schema) {
            return new SchemaWithName($matches['name'], $schema);
        }

        throw new InvalidArgumentException(sprintf('Can not resolve reference "%s"', $reference->getReference()));
    }
}

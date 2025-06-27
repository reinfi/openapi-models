<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use InvalidArgumentException;
use openapiphp\openapi\spec\OpenApi;
use openapiphp\openapi\spec\Reference;
use openapiphp\openapi\spec\Schema;
use Reinfi\OpenApiModels\Exception\InvalidReferenceException;
use Reinfi\OpenApiModels\Model\SchemaWithName;
use Throwable;

readonly class ReferenceResolver
{
    public function resolve(OpenApi $openApi, Reference $reference): SchemaWithName
    {
        if (preg_match(
            '/^(?<fileOrUrl>[^#]+)?#\/components\/(?<type>.+)\/(?<name>.+)$/',
            $reference->getReference(),
            $matches
        ) !== 1) {
            try {
                $resolvedSchema = $reference->resolve();

                if ($resolvedSchema instanceof Schema) {
                    return new SchemaWithName(OpenApiType::Schemas, '', $resolvedSchema);
                }
            } catch (Throwable $throwable) {
                throw new InvalidArgumentException(
                    sprintf('Invalid reference "%s" given, does not match pattern', $reference->getReference()),
                    previous: $throwable,
                );
            }

            throw new InvalidArgumentException(
                sprintf('Invalid reference "%s" given, does not match pattern', $reference->getReference()),
            );
        }

        $openApiType = OpenApiType::tryFrom($matches['type']);

        if ($openApiType === null) {
            throw new InvalidReferenceException($matches['type'], $reference->getReference());
        }

        $schema = match ($openApiType) {
            OpenApiType::Schemas => $openApi->components->schemas[$matches['name']] ?? null,
            OpenApiType::Responses, OpenApiType::RequestBodies => throw new InvalidReferenceException(
                $openApiType->value,
                $reference->getReference(),
            ),
        };

        if ($schema instanceof Schema) {
            return new SchemaWithName($openApiType, $matches['name'], $schema);
        }

        throw new InvalidArgumentException(sprintf('Can not resolve reference "%s"', $reference->getReference()));
    }
}

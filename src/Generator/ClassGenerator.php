<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\Schema;
use Nette\PhpGenerator\PhpNamespace;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Exception\UnknownMediaTypeException;

readonly class ClassGenerator
{
    public function __construct(
        private ClassTransformer $classTransformer,
        private NamespaceResolver $namespaceResolver,
    ) {
    }

    /**
     * @return array<value-of<OpenApiType>, PhpNamespace>
     */
    public function generate(OpenApi $openApi, Configuration $configuration): array
    {
        $this->namespaceResolver->initialize($configuration);

        $this->addSchemas($openApi);
        $this->buildMediaTypeComponents(
            OpenApiType::RequestBodies,
            $openApi,
            $openApi->components->requestBodies ?? []
        );
        $this->buildMediaTypeComponents(OpenApiType::Responses, $openApi, $openApi->components->responses ?? []);

        return $this->namespaceResolver->getNamespaces();
    }

    private function addSchemas(OpenApi $openApi): void
    {
        $schemas = $openApi->components->schemas ?? [];
        if (count($schemas) === 0) {
            return;
        }

        $namespace = $this->namespaceResolver->resolveNamespace(OpenApiType::Schemas);

        foreach ($schemas as $name => $schema) {
            if ($schema instanceof Schema) {
                $this->classTransformer->transform($openApi, $name, $schema, $namespace);
            }
        }
    }

    /**
     * @param array<RequestBody|Response|Reference> $components
     */
    private function buildMediaTypeComponents(OpenApiType $openApiType, OpenApi $openApi, array $components): void
    {
        if (count($components) === 0) {
            return;
        }

        $namespace = $this->namespaceResolver->resolveNamespace($openApiType);

        foreach ($components as $name => $component) {
            if ($component instanceof Reference) {
                continue;
            }

            $hasMultipleMediaTypes = count($component->content) > 1;

            foreach ($component->content as $mediaTypeName => $mediaType) {
                if ($mediaType->schema instanceof Schema) {
                    $className = $hasMultipleMediaTypes ? $name . $this->mapMediaTypeToSuffix($mediaTypeName) : $name;
                    $this->classTransformer->transform($openApi, $className, $mediaType->schema, $namespace);
                }
            }
        }
    }

    private function mapMediaTypeToSuffix(string $mediaType): string
    {
        return match ($mediaType) {
            'application/json' => 'Json',
            'application/x-www-form-urlencoded' => 'Form',
            'application/xml' => 'Xml',
            '*/*' => '',
            default => throw new UnknownMediaTypeException($mediaType),
        };
    }
}

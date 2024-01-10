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
use Reinfi\OpenApiModels\Exception\OnlyJsonContentTypeSupported;
use Reinfi\OpenApiModels\Model\Imports;

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

        $this->addSchemas($configuration, $openApi);
        $this->buildMediaTypeComponents(
            $configuration,
            OpenApiType::RequestBodies,
            $openApi,
            $openApi->components->requestBodies ?? []
        );
        $this->buildMediaTypeComponents(
            $configuration,
            OpenApiType::Responses,
            $openApi,
            $openApi->components->responses ?? []
        );

        return $this->namespaceResolver->getNamespaces();
    }

    private function addSchemas(Configuration $configuration, OpenApi $openApi): void
    {
        $schemas = $openApi->components->schemas ?? [];
        if (count($schemas) === 0) {
            return;
        }

        $namespace = $this->namespaceResolver->resolveNamespace(OpenApiType::Schemas);
        $imports = new Imports($namespace);

        foreach ($schemas as $name => $schema) {
            if ($schema instanceof Schema) {
                $this->classTransformer->transform($configuration, $openApi, $name, $schema, $namespace, $imports);
            }
        }

        $imports->copyImports();
    }

    /**
     * @param array<RequestBody|Response|Reference> $components
     */
    private function buildMediaTypeComponents(
        Configuration $configuration,
        OpenApiType $openApiType,
        OpenApi $openApi,
        array $components
    ): void {
        if (count($components) === 0) {
            return;
        }

        $namespace = $this->namespaceResolver->resolveNamespace($openApiType);
        $imports = new Imports($namespace);

        foreach ($components as $name => $component) {
            if ($component instanceof Reference) {
                continue;
            }

            if (count($component->content) === 0) {
                continue;
            }

            // Only json is supported, this is due to the fact that we only implement serialization for json format.
            $mediaType = $component->content['application/json'] ?? null;

            if ($mediaType === null) {
                throw new OnlyJsonContentTypeSupported(array_keys($component->content));
            }

            if ($mediaType->schema !== null) {
                $class = $this->classTransformer->transform(
                    $configuration,
                    $openApi,
                    $name,
                    $mediaType->schema,
                    $namespace,
                    $imports
                );

                if ($class->getComment() === null && is_string($component->description) && strlen(
                    $component->description
                ) > 0) {
                    $class->addComment($component->description);
                }
            }
        }

        $imports->copyImports();
    }
}

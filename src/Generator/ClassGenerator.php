<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use openapiphp\openapi\spec\OpenApi;
use openapiphp\openapi\spec\Reference;
use openapiphp\openapi\spec\RequestBody;
use openapiphp\openapi\spec\Response;
use openapiphp\openapi\spec\Schema;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Exception\OnlyJsonContentTypeSupported;
use Reinfi\OpenApiModels\Model\ClassModel;

readonly class ClassGenerator
{
    public function __construct(
        private ClassTransformer $classTransformer,
        private NamespaceResolver $namespaceResolver,
    ) {
    }

    /**
     * @return ClassModel[]
     */
    public function generate(OpenApi $openApi, Configuration $configuration): array
    {
        $this->namespaceResolver->initialize($configuration);

        return [
            ...$this->addSchemas($configuration, $openApi),
            ...$this->buildMediaTypeComponents(
                $configuration,
                OpenApiType::RequestBodies,
                $openApi,
                $openApi->components->requestBodies ?? []
            ),
            ...$this->buildMediaTypeComponents(
                $configuration,
                OpenApiType::Responses,
                $openApi,
                $openApi->components->responses ?? []
            ),
        ];
    }

    /**
     * @return ClassModel[]
     */
    private function addSchemas(Configuration $configuration, OpenApi $openApi): array
    {
        $schemas = $openApi->components->schemas ?? [];
        if (count($schemas) === 0) {
            return [];
        }

        $models = [];

        foreach ($schemas as $name => $schema) {
            if ($schema instanceof Reference) {
                $schema = $schema->resolve();
            }

            if ($schema instanceof Schema) {
                $classModel = $this->classTransformer->transform(
                    $configuration,
                    $openApi,
                    OpenApiType::Schemas,
                    $name,
                    $schema
                );

                if ($classModel !== null) {
                    $models[] = $classModel;
                    foreach ($classModel->getInlineModels() as $inlineModel) {
                        $models[] = $inlineModel;
                    }
                }
            }
        }

        $this->addClassesForImports($models);

        return $models;
    }

    /**
     * @param array<RequestBody|Response|Reference> $components
     *
     * @return ClassModel[]
     */
    private function buildMediaTypeComponents(
        Configuration $configuration,
        OpenApiType $openApiType,
        OpenApi $openApi,
        array $components
    ): array {
        if (count($components) === 0) {
            return [];
        }

        $models = [];

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
                $classModel = $this->classTransformer->transform(
                    $configuration,
                    $openApi,
                    $openApiType,
                    $name,
                    $mediaType->schema,
                );

                if ($classModel !== null) {
                    if ($classModel->class->getComment() === null && is_string($component->description) && strlen(
                        $component->description
                    ) > 0) {
                        $classModel->class->addComment($component->description);
                    }

                    $models[] = $classModel;
                    foreach ($classModel->getInlineModels() as $inlineModel) {
                        $models[] = $inlineModel;
                    }
                }
            }
        }

        $this->addClassesForImports($models);

        return $models;
    }

    /**
     * @param ClassModel[] $classModels
     */
    private function addClassesForImports(array $classModels): void
    {
        foreach ($classModels as $classModel) {
            foreach ($classModel->imports->getImports() as $import) {
                $importModel = $this->findClassModel($classModels, $import);

                if ($importModel !== null) {
                    $classModel->namespace->add($importModel->class);
                }
            }

            $classModel->imports->copyImports();
        }
    }

    /**
     * @param ClassModel[] $classModels
     */
    private function findClassModel(array $classModels, string $import): ?ClassModel
    {
        foreach ($classModels as $classModel) {
            $fullClassName = $classModel->namespace->resolveName($classModel->className);

            if ($fullClassName === $import) {
                return $classModel;
            }
        }

        return null;
    }
}

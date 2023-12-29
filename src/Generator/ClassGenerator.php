<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use cebe\openapi\spec\OpenApi;
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
    ) {
    }

    /**
     * @return array<string, PhpNamespace>
     */
    public function generate(OpenApi $openApi, Configuration $configuration): array
    {
        return [
            'schemas' => $this->addSchemas($openApi, $configuration),
            'requestBodies' => $this->buildMediaTypeComponents(
                OpenApiType::RequestBodies,
                $openApi,
                $configuration,
                $openApi->components->requestBodies ?? []
            ),
            'responses' => $this->buildMediaTypeComponents(
                OpenApiType::Responses,
                $openApi,
                $configuration,
                $openApi->components->responses ?? []
            ),
        ];
    }

    private function addSchemas(OpenApi $openApi, Configuration $configuration): PhpNamespace
    {
        $namespace = $this->buildNamespace($configuration, OpenApiType::Schemas);

        $schemas = $openApi->components->schemas ?? [];

        foreach ($schemas as $name => $schema) {
            if ($schema instanceof Schema) {
                $this->classTransformer->transform($openApi, $name, $schema, $namespace);
            }
        }

        return $namespace;
    }

    /**
     * @param Response[]|RequestBody[] $components
     */
    private function buildMediaTypeComponents(
        OpenApiType $openApiType,
        OpenApi $openApi,
        Configuration $configuration,
        array $components
    ): PhpNamespace {
        $namespace = $this->buildNamespace($configuration, $openApiType);

        foreach ($components as $name => $component) {
            $hasMultipleMediaTypes = count($component->content) > 1;

            foreach ($component->content as $mediaTypeName => $mediaType) {
                if ($mediaType->schema instanceof Schema) {
                    $className = $hasMultipleMediaTypes ? $name . $this->mapMediaTypeToSuffix($mediaTypeName) : $name;
                    $this->classTransformer->transform($openApi, $className, $mediaType->schema, $namespace);
                }
            }
        }

        return $namespace;
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

    private function buildNamespace(Configuration $configuration, OpenApiType $openApiType): PhpNamespace
    {
        if (strlen($configuration->namespace) === 0) {
            return new PhpNamespace($this->mapOpenApiTypeToNamespace($openApiType));
        }

        return new PhpNamespace(sprintf(
            '%s\%s',
            $configuration->namespace,
            $this->mapOpenApiTypeToNamespace($openApiType)
        ));
    }

    private function mapOpenApiTypeToNamespace(OpenApiType $type): string
    {
        return match ($type) {
            OpenApiType::Schemas => 'Schema',
            OpenApiType::RequestBodies => 'RequestBody',
            OpenApiType::Responses => 'Response',
        };
    }
}

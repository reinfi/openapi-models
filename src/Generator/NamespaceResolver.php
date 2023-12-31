<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use Nette\PhpGenerator\PhpNamespace;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Exception\NotRegisteredNamespaceException;

class NamespaceResolver
{
    /**
     * @var array<value-of<OpenApiType>, PhpNamespace>
     */
    private array $openApiTypeToNamespace = [];

    /**
     * @return array<value-of<OpenApiType>, PhpNamespace>
     */
    public function getNamespaces(): array
    {
        return $this->openApiTypeToNamespace;
    }

    public function resolveNamespace(OpenApiType $openApiType): PhpNamespace
    {
        $namespace = $this->openApiTypeToNamespace[$openApiType->value] ?? null;

        if ($namespace === null) {
            throw new NotRegisteredNamespaceException($openApiType);
        }

        return $namespace;
    }

    public function initialize(Configuration $configuration): void
    {
        foreach (OpenApiType::cases() as $openApiType) {
            $this->openApiTypeToNamespace[$openApiType->value] = $this->buildNamespace($configuration, $openApiType);
        }
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

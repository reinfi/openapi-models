<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use Nette\PhpGenerator\PhpNamespace;
use openapiphp\openapi\spec\Schema;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Exception\NotRegisteredNamespaceException;

class NamespaceResolver
{
    /**
     * @var array<value-of<OpenApiType>, PhpNamespace>
     */
    private array $openApiTypeToNamespace = [];

    public function resolveNamespace(OpenApiType $openApiType, Schema $schema): PhpNamespace
    {
        // @phpstan-ignore-next-line
        $xPhpNamespace = $schema->{'x-php-namespace'} ?? null;
        $openApiNamespace = $this->resolveOpenApiTypeNamespace($openApiType);

        if (! is_string($xPhpNamespace)) {
            return clone $openApiNamespace;
        }

        return new PhpNamespace($openApiNamespace->getName() . '\\' . $xPhpNamespace);
    }

    public function initialize(Configuration $configuration): void
    {
        foreach (OpenApiType::cases() as $openApiType) {
            $this->openApiTypeToNamespace[$openApiType->value] = $this->buildNamespace($configuration, $openApiType);
        }
    }

    private function resolveOpenApiTypeNamespace(OpenApiType $openApiType): PhpNamespace
    {
        $namespace = $this->openApiTypeToNamespace[$openApiType->value] ?? null;

        if ($namespace === null) {
            throw new NotRegisteredNamespaceException($openApiType);
        }

        return $namespace;
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

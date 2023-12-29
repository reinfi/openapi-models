<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use Nette\PhpGenerator\PhpNamespace;
use Reinfi\OpenApiModels\Configuration\Configuration;

readonly class ClassGenerator
{
    public function __construct(
        private ClassTransformer $classTransformer,
    ) {
    }

    public function generate(OpenApi $openApi, Configuration $configuration): PhpNamespace
    {
        $schemaNamespace = $this->buildNamespace($configuration, 'Schema');

        $schemas = $openApi->components->schemas ?? [];
        foreach ($schemas as $name => $schema) {
            if ($schema instanceof Schema) {
                $this->classTransformer->transform($openApi, $name, $schema, $schemaNamespace);
            }
        }

        return $schemaNamespace;
    }

    private function buildNamespace(Configuration $configuration, string $namespaceSuffix): PhpNamespace
    {
        if (strlen($configuration->namespace) === 0) {
            return new PhpNamespace($namespaceSuffix);
        }

        return new PhpNamespace(sprintf('%s\%s', $configuration->namespace, $namespaceSuffix));
    }
}

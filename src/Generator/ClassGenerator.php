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
        $namespace = new PhpNamespace($configuration->namespace);

        $schemas = $openApi->components->schemas ?? [];
        foreach ($schemas as $name => $schema) {
            if ($schema instanceof Schema) {
                $this->classTransformer->transform($openApi, $name, $schema, $namespace);
            }
        }

        return $namespace;
    }
}

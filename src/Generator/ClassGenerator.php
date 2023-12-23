<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use cebe\openapi\spec\OpenApi;
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

        foreach ($openApi->components->schemas as $name => $schema) {
            $this->classTransformer->transform($openApi, $name, $schema, $namespace);
        }

        return $namespace;
    }
}

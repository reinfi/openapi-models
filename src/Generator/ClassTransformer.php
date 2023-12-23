<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

readonly class ClassTransformer
{
    public function __construct(
        private PropertyTransformer $propertyTransformer
    ) {
    }

    public function transform(OpenApi $openApi, string $name, Schema $schema, PhpNamespace $namespace): ClassType
    {
        $class = $namespace->addClass($name)->setReadOnly();

        $constructor = $class->addMethod('__construct');

        foreach ($schema->properties as $propertyName => $property) {
            $parameter = $this->propertyTransformer->transform(
                $openApi,
                $namespace,
                $constructor,
                $propertyName,
                $property,
                in_array($propertyName, $schema->required ?: [], true)
            );

            if ($parameter->getType() === 'object' && $property instanceof Schema) {
                $inlineType = $this->transformInlineObject($openApi, $name, $propertyName, $property, $namespace);

                $parameter->setType($namespace->resolveName($inlineType));
            }
        }

        return $class;
    }

    private function transformInlineObject(
        OpenApi $openApi,
        string $parentName,
        string $propertyName,
        Schema $schema,
        PhpNamespace $namespace
    ): string {
        $className = $parentName . ucfirst($propertyName);

        $this->transform($openApi, $className, $schema, $namespace);

        return $className;
    }
}

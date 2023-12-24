<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PromotedParameter;

readonly class ClassTransformer
{
    public function __construct(
        private PropertyTransformer $propertyTransformer,
        private TypeTransformer $typeTransformer,
        private ReferenceResolver $referenceResolver,
    ) {
    }

    public function transform(OpenApi $openApi, string $name, Schema $schema, PhpNamespace $namespace): ClassType
    {
        $class = $namespace->addClass($name)->setReadOnly();

        $constructor = $class->addMethod('__construct');

        $schemasForClass = $this->resolveSchemasForClass($openApi, $schema);

        foreach ($schemasForClass as $schema) {
            foreach ($schema->properties as $propertyName => $property) {
                $type = $this->typeTransformer->transform($openApi, $property, $namespace);

                $parameter = $this->propertyTransformer->transform(
                    $constructor,
                    $propertyName,
                    $property,
                    in_array($propertyName, $schema->required ?: [], true),
                    $type
                );

                if ($type === Types::Object && $property instanceof Schema) {
                    $inlineType = $this->transformInlineObject($openApi, $name, $propertyName, $property, $namespace);

                    $parameter->setType($namespace->resolveName($inlineType));
                }

                if ($type === Types::Enum && $property instanceof Schema) {
                    $enumType = $this->transformEnum($name, $propertyName, $property, $namespace);

                    $parameter->setType($namespace->resolveName($enumType));
                }

                if ($type === Types::Array && $property instanceof Schema) {
                    $this->resolveArrayType($openApi, $name, $propertyName, $property, $namespace, $parameter);
                }

                if ($type === Types::OneOf && $property instanceof Schema) {
                    $oneOfType = $this->transformOneOf($openApi, $name, $propertyName, $property->oneOf, $namespace);

                    $parameter->setType($oneOfType);
                }
            }
        }

        return $class;
    }

    /**
     * @return Schema[]
     */
    private function resolveSchemasForClass(OpenApi $openApi, Schema $schema): array
    {
        if (is_array($schema->allOf)) {
            return array_map(
                function (Schema|Reference $schema) use ($openApi): Schema {
                    if ($schema instanceof Reference) {
                        return $this->referenceResolver->resolve($openApi, $schema)->schema;
                    }

                    return $schema;
                },
                $schema->allOf
            );
        }

        return [$schema];
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

    private function transformEnum(
        string $parentName,
        string $propertyName,
        Schema $schema,
        PhpNamespace $namespace
    ): string {
        $enumName = $parentName . ucfirst($propertyName);

        $enum = $namespace->addEnum($enumName);
        $enum->setType(match ($schema->type) {
            'number' => 'int',
            default => 'string'
        });
        foreach ($schema->enum as $enumValue) {
            $enumCaseName = ucfirst($enumValue);
            $enum->addCase($enumCaseName, $enumValue);
        }

        return $enumName;
    }

    private function resolveArrayType(
        OpenApi $openApi,
        string $parentName,
        string $propertyName,
        Schema $schema,
        PhpNamespace $namespace,
        PromotedParameter $parameter,
    ): void {
        $itemsSchema = $schema->items;
        $arrayType = $this->typeTransformer->transform($openApi, $itemsSchema, $namespace);

        if ($arrayType === Types::Object) {
            $arrayType = $namespace->resolveName(
                $this->transformInlineObject($openApi, $parentName, $propertyName, $schema->items, $namespace)
            );
        }

        $parameter->setType('array')->addComment(
            sprintf('@var %s[] $%s', $namespace->simplifyName($arrayType), $parameter->getName())
        );
    }

    /**
     * @param array<Schema|Reference> $oneOf
     */
    private function transformOneOf(
        OpenApi $openApi,
        string $parentName,
        string $propertyName,
        array $oneOf,
        PhpNamespace $namespace
    ): string {
        $resolvedTypes = [];

        $countInlineObjects = 0;

        foreach ($oneOf as $oneOfElement) {
            if ($oneOfElement instanceof Schema) {
                $resolvedTypes[] = $namespace->resolveName(
                    $this->transformInlineObject(
                        $openApi,
                        $parentName,
                        $propertyName . ++$countInlineObjects,
                        $oneOfElement,
                        $namespace
                    )
                );
            }

            if ($oneOfElement instanceof Reference) {
                $resolvedTypes[] = $this->typeTransformer->transform($openApi, $oneOfElement, $namespace);
            }
        }

        return join('|', $resolvedTypes);
    }
}

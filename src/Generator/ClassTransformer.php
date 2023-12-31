<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PromotedParameter;
use Reinfi\OpenApiModels\Exception\UnresolvedArrayTypeException;
use Reinfi\OpenApiModels\Exception\UnsupportedTypeForOneOfException;

readonly class ClassTransformer
{
    public function __construct(
        private PropertyResolver $propertyResolver,
        private TypeResolver $typeResolver,
        private ReferenceResolver $referenceResolver,
    ) {
    }

    public function transform(
        OpenApi $openApi,
        string $name,
        Schema|Reference $schema,
        PhpNamespace $namespace
    ): ClassType {
        $class = $namespace->addClass($name)->setReadOnly();

        if ($schema instanceof Reference) {
            return $this->resolveReferenceForClass($openApi, $schema, $class);
        }

        $constructor = $class->addMethod('__construct');

        $schemasForClass = $this->resolveSchemasForClass($openApi, $schema);

        foreach ($schemasForClass as $schema) {
            foreach ($schema->properties as $propertyName => $property) {
                $type = $this->typeResolver->resolve($openApi, $property);

                $parameter = $this->propertyResolver->resolve(
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

    private function resolveReferenceForClass(OpenApi $openApi, Reference $reference, ClassType $class): ClassType
    {
        $class->setExtends($this->typeResolver->resolve($openApi, $reference));

        return $class;
    }

    /**
     * @return Schema[]
     */
    private function resolveSchemasForClass(OpenApi $openApi, Schema $schema): array
    {
        if (is_array($schema->allOf) && count($schema->allOf) > 0) {
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
            $enumCaseName = match ($enum->getType()) {
                'int' => sprintf('Value%u', $enumValue),
                default => ucfirst($enumValue),
            };
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
        $nullablePart = $parameter->isNullable() ? '|null' : '';
        $itemsSchema = $schema->items;
        if ($itemsSchema === null) {
            $parameter->setType(sprintf('array%s', $nullablePart));
            return;
        }

        $arrayType = $this->typeResolver->resolve($openApi, $itemsSchema);

        if ($arrayType === Types::Object && $itemsSchema instanceof Schema) {
            $arrayType = $namespace->resolveName(
                $this->transformInlineObject($openApi, $parentName, $propertyName, $itemsSchema, $namespace)
            );
        }

        if ($arrayType === Types::Enum && $itemsSchema instanceof Schema) {
            $arrayType = $namespace->resolveName(
                $this->transformEnum($parentName, $propertyName, $itemsSchema, $namespace)
            );
        }

        if ($arrayType === Types::OneOf && $itemsSchema instanceof Schema && is_array($itemsSchema->oneOf)) {
            $oneOfArrayType = $this->transformOneOf(
                $openApi,
                $parentName,
                $propertyName,
                $itemsSchema->oneOf,
                $namespace,
                true
            );
            $parameter->setType('array')->addComment(
                sprintf('@var array<%s>%s $%s', $oneOfArrayType, $nullablePart, $parameter->getName())
            );
            return;
        }

        if ($arrayType instanceof Types) {
            throw new UnresolvedArrayTypeException($arrayType->value);
        }

        $parameter->setType('array')->addComment(
            sprintf('@var %s[]%s $%s', $namespace->simplifyName($arrayType), $nullablePart, $parameter->getName())
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
        PhpNamespace $namespace,
        bool $simplifyName = false
    ): string {
        $resolvedTypes = [];

        $countInlineObjects = 0;

        foreach ($oneOf as $oneOfElement) {
            if ($oneOfElement instanceof Schema) {
                $resolvedType = $this->typeResolver->resolve($openApi, $oneOfElement);

                $resolvedTypes[] = match ($resolvedType) {
                    Types::Object => $namespace->resolveName(
                        $this->transformInlineObject(
                            $openApi,
                            $parentName,
                            $propertyName . ++$countInlineObjects,
                            $oneOfElement,
                            $namespace
                        )
                    ),
                    Types::Enum => $namespace->resolveName(
                        $this->transformEnum(
                            $parentName,
                            $propertyName . ++$countInlineObjects,
                            $oneOfElement,
                            $namespace
                        )
                    ),
                    Types::OneOf, Types::AnyOf, Types::Array => throw new UnsupportedTypeForOneOfException(
                        $resolvedType->value
                    ),
                    default => $resolvedType,
                };
            }

            if ($oneOfElement instanceof Reference) {
                $resolvedTypes[] = $this->typeResolver->resolve($openApi, $oneOfElement);
            }
        }

        if ($simplifyName) {
            $resolvedTypes = array_map(
                static fn (string $type): string => $namespace->simplifyName($type),
                $resolvedTypes
            );
        }

        return join('|', $resolvedTypes);
    }
}

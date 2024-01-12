<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use DateTimeInterface;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Exception\UnresolvedArrayTypeException;
use Reinfi\OpenApiModels\Exception\UnsupportedTypeForArrayException;
use Reinfi\OpenApiModels\Exception\UnsupportedTypeForOneOfException;
use Reinfi\OpenApiModels\Model\ArrayType;
use Reinfi\OpenApiModels\Model\Imports;

readonly class ClassTransformer
{
    public function __construct(
        private PropertyResolver $propertyResolver,
        private TypeResolver $typeResolver,
        private ReferenceResolver $referenceResolver,
        private SerializableResolver $serializableResolver,
        private ArrayObjectResolver $arrayObjectResolver,
    ) {
    }

    public function transform(
        Configuration $configuration,
        OpenApi $openApi,
        string $name,
        Schema|Reference $schema,
        PhpNamespace $namespace,
        Imports $imports
    ): ClassType {
        $class = $namespace->addClass($name)->setReadOnly();

        if ($schema instanceof Schema && is_string($schema->description) && strlen($schema->description) > 0) {
            $class->addComment($schema->description);
        }

        $schemaType = $this->typeResolver->resolve($openApi, $schema);

        if ($schemaType instanceof ClassReference) {
            return $this->resolveReferenceForClass($class, $schemaType, $imports);
        }

        assert($schema instanceof Schema);

        $constructor = $class->addMethod('__construct');

        if ($schemaType === Types::Object || $schemaType === Types::AllOf) {
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

                    if ($type instanceof ClassReference) {
                        $imports->addImport($type->name);
                    }

                    if ($type === Types::Date || $type === Types::DateTime) {
                        if ($configuration->dateTimeAsObject) {
                            $imports->addImport(DateTimeInterface::class);
                            $parameter->setType(DateTimeInterface::class);
                        } else {
                            $parameter->setType('string');
                        }
                    }

                    if ($type === Types::Object && $property instanceof Schema) {
                        $inlineType = $this->transformInlineObject(
                            $configuration,
                            $openApi,
                            $name,
                            $propertyName,
                            $property,
                            $namespace,
                            $imports
                        );

                        $parameter->setType($namespace->resolveName($inlineType));
                    }

                    if ($type === Types::Enum && $property instanceof Schema) {
                        $enumType = $this->transformEnum($name, $propertyName, $property, $namespace);

                        $parameter->setType($namespace->resolveName($enumType));
                    }

                    if ($type === Types::Array && $property instanceof Schema) {
                        $arrayType = $this->resolveArrayType(
                            $configuration,
                            $openApi,
                            $name,
                            $propertyName,
                            $parameter->isNullable(),
                            $property,
                            $namespace,
                            $imports,
                        );

                        $parameter->setType('array');

                        if ($arrayType !== null) {
                            $parameter->addComment($arrayType->docType);
                            $imports->addImport(...$arrayType->imports);
                        }
                    }

                    if ($type === Types::OneOf && $property instanceof Schema) {
                        $oneOfType = $this->transformOneOf(
                            $configuration,
                            $openApi,
                            $name,
                            $propertyName,
                            $property->oneOf,
                            $namespace,
                            $imports
                        );

                        $parameter->setType($oneOfType);
                    }
                }
            }
        }

        if ($schemaType === Types::Array) {
            $arrayType = $this->resolveArrayType(
                $configuration,
                $openApi,
                $name,
                'items',
                $schema->nullable ?? false,
                $schema,
                $namespace,
                $imports
            );
            if ($arrayType !== null) {
                $this->arrayObjectResolver->resolve($class, $constructor, $arrayType, $imports);
            }
        }

        $serializableType = $this->serializableResolver->needsSerialization($class);
        if ($serializableType !== SerializableType::None) {
            $this->serializableResolver->addSerialization(
                $serializableType,
                $configuration,
                $openApi,
                $schema,
                $namespace,
                $class,
                $constructor
            );
        }

        return $class;
    }

    private function resolveReferenceForClass(
        ClassType $class,
        ClassReference $classReference,
        Imports $imports,
    ): ClassType {
        $imports->addImport($classReference->name);
        $class->setExtends($classReference->name);

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
        Configuration $configuration,
        OpenApi $openApi,
        string $parentName,
        string $propertyName,
        Schema $schema,
        PhpNamespace $namespace,
        Imports $imports,
    ): string {
        $className = $parentName . ucfirst($propertyName);

        $this->transform($configuration, $openApi, $className, $schema, $namespace, $imports);

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
        Configuration $configuration,
        OpenApi $openApi,
        string $parentName,
        string $propertyName,
        bool $nullable,
        Schema $schema,
        PhpNamespace $namespace,
        Imports $imports,
    ): ?ArrayType {
        $itemsSchema = $schema->items;
        if ($itemsSchema === null) {
            return null;
        }

        $nullablePart = $nullable ? '|null' : '';

        $arrayType = $this->typeResolver->resolve($openApi, $itemsSchema);

        if ($arrayType === Types::Object && $itemsSchema instanceof Schema) {
            $arrayType = $namespace->resolveName(
                $this->transformInlineObject(
                    $configuration,
                    $openApi,
                    $parentName,
                    $propertyName,
                    $itemsSchema,
                    $namespace,
                    $imports
                )
            );
        }

        if ($arrayType === Types::Enum && $itemsSchema instanceof Schema) {
            $arrayType = $namespace->resolveName(
                $this->transformEnum($parentName, $propertyName, $itemsSchema, $namespace)
            );
        }

        if ($arrayType === Types::OneOf && $itemsSchema instanceof Schema && is_array($itemsSchema->oneOf)) {
            $oneOfArrayType = $this->transformOneOf(
                $configuration,
                $openApi,
                $parentName,
                $propertyName,
                $itemsSchema->oneOf,
                $namespace,
                $imports
            );

            if (str_contains($oneOfArrayType, DateTimeInterface::class)) {
                throw new UnsupportedTypeForArrayException('date or datetime in oneOf');
            }

            return new ArrayType($oneOfArrayType, $nullable, sprintf(
                '@var array<%s>%s $%s',
                $oneOfArrayType,
                $nullablePart,
                $propertyName
            ));
        }

        if (in_array($arrayType, [Types::Date, Types::DateTime], true)) {
            return new ArrayType(DateTimeInterface::class, $nullable, sprintf(
                '@var array<%s>%s $%s',
                DateTimeInterface::class,
                $nullablePart,
                $propertyName
            ), [DateTimeInterface::class]);
        }

        if ($arrayType instanceof Types) {
            throw new UnresolvedArrayTypeException($arrayType->value);
        }

        if ($arrayType instanceof ClassReference) {
            return new ArrayType($arrayType, $nullable, sprintf(
                '@var %s[]%s $%s',
                $arrayType->name,
                $nullablePart,
                $propertyName
            ), [$arrayType->name]);
        }

        return new ArrayType($arrayType, $nullable, sprintf(
            '@var %s[]%s $%s',
            $namespace->simplifyName($arrayType),
            $nullablePart,
            $propertyName
        ));
    }

    /**
     * @param array<Schema|Reference> $oneOf
     */
    private function transformOneOf(
        Configuration $configuration,
        OpenApi $openApi,
        string $parentName,
        string $propertyName,
        array $oneOf,
        PhpNamespace $namespace,
        Imports $imports,
    ): string {
        $resolvedTypes = [];

        $countInlineObjects = 0;

        foreach ($oneOf as $oneOfElement) {
            if ($oneOfElement instanceof Schema) {
                $resolvedType = $this->typeResolver->resolve($openApi, $oneOfElement);

                $resolvedTypes[] = match ($resolvedType) {
                    Types::Object => $namespace->resolveName(
                        $this->transformInlineObject(
                            $configuration,
                            $openApi,
                            $parentName,
                            $propertyName . ++$countInlineObjects,
                            $oneOfElement,
                            $namespace,
                            $imports
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
                    Types::DateTime, Types::Date => $configuration->dateTimeAsObject ? DateTimeInterface::class : 'string',
                    Types::AllOf, Types::OneOf, Types::AnyOf, Types::Array => throw new UnsupportedTypeForOneOfException(
                        $resolvedType->value
                    ),
                    default => $resolvedType,
                };
            }

            if ($oneOfElement instanceof Reference) {
                $classReference = $this->typeResolver->resolve($openApi, $oneOfElement);
                $imports->addImport($classReference->name);
                $resolvedTypes[] = $classReference->name;
            }
        }

        return join('|', $resolvedTypes);
    }
}

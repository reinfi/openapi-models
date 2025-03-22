<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use DateTimeInterface;
use InvalidArgumentException;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Helpers;
use Nette\PhpGenerator\PhpNamespace;
use openapiphp\openapi\spec\OpenApi;
use openapiphp\openapi\spec\Reference;
use openapiphp\openapi\spec\Schema;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Exception\InvalidEnumSchema;
use Reinfi\OpenApiModels\Exception\UnresolvedArrayTypeException;
use Reinfi\OpenApiModels\Exception\UnsupportedTypeForArrayException;
use Reinfi\OpenApiModels\Exception\UnsupportedTypeForDictionaryException;
use Reinfi\OpenApiModels\Exception\UnsupportedTypeForOneOfException;
use Reinfi\OpenApiModels\Model\ArrayType;
use Reinfi\OpenApiModels\Model\Imports;
use Reinfi\OpenApiModels\Model\OneOfReference;
use Reinfi\OpenApiModels\Model\OneOfType;
use Reinfi\OpenApiModels\Model\ScalarType;
use Reinfi\OpenApiModels\Serialization\SerializableResolver;

readonly class ClassTransformer
{
    public function __construct(
        private PropertyResolver $propertyResolver,
        private TypeResolver $typeResolver,
        private ReferenceResolver $referenceResolver,
        private SerializableResolver $serializableResolver,
        private ArrayObjectResolver $arrayObjectResolver,
        private AllOfPropertySchemaResolver $allOfPropertySchemaResolver,
        private DictionaryResolver $dictionaryResolver,
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
        $class = $namespace->addClass($name)
            ->setReadOnly();

        if ($schema instanceof Schema && $schema->description !== null && $schema->description !== '') {
            $class->addComment($schema->description);
        }

        $schemaType = $this->typeResolver->resolve($openApi, $schema);

        if ($schemaType instanceof ClassReference) {
            return $this->resolveReferenceForClass($class, $schemaType, $imports);
        }

        if (is_string($schemaType) || in_array(
            $schemaType,
            [Types::Date, Types::DateTime, Types::OneOf, Types::Null],
            true
        )) {
            if ($class->getName() !== null) {
                $namespace->removeClass($class->getName());
            }

            return $class;
        }

        $constructor = $class->addMethod('__construct');

        if ($schemaType === Types::Object || $schemaType === Types::AllOf) {
            $schemasForClass = $this->resolveSchemasForClass($openApi, $schema);

            $requiredProperties = array_reduce(
                $schemasForClass,
                static fn (array $requiredProperties, Schema $schema) => array_merge(
                    $requiredProperties,
                    is_array($schema->required) ? $schema->required : []
                ),
                []
            );
            $properties = array_reduce(
                $schemasForClass,
                static fn (array $properties, Schema $schema) => array_merge(
                    $properties,
                    $schema->properties ?? []
                ),
                []
            );

            uksort(
                $properties,
                static fn (
                    string $propertyNameFirst,
                    string $propertyNameSecond
                ): int => in_array(
                    $propertyNameSecond,
                    $requiredProperties,
                    true
                ) <=> in_array($propertyNameFirst, $requiredProperties, true)
            );

            foreach ($properties as $propertyName => $property) {
                if (! $property instanceof Schema && ! $property instanceof Reference) {
                    throw new InvalidArgumentException(sprintf(
                        'Property "%s" must be an instance of Schema or Reference',
                        $name
                    ));
                }

                $type = $this->typeResolver->resolve($openApi, $property);

                if ($type === Types::AllOf) {
                    $allOfType = $this->allOfPropertySchemaResolver->resolve($openApi, $property, $propertyName);
                    $type = $allOfType->type;
                    $property = $allOfType->schema;
                }

                $parameter = $this->propertyResolver->resolve(
                    $constructor,
                    $propertyName,
                    $property,
                    in_array($propertyName, $requiredProperties, true),
                    $type
                );

                if ($type instanceof ClassReference) {
                    $imports->addImport($type->name);
                }

                if ($type instanceof OneOfReference) {
                    $property = $type->schema;
                    $type = Types::OneOf;
                }

                if ($type === Types::Date || $type === Types::DateTime) {
                    if ($configuration->dateTimeAsObject) {
                        $imports->addImport(DateTimeInterface::class);
                        $parameter->setType(DateTimeInterface::class);
                    } else {
                        $parameter->setType('string');
                    }
                }

                if ($type === Types::Object) {
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

                if ($type === Types::Enum) {
                    $enumType = $this->transformEnum($name, $propertyName, $property, $namespace);

                    $parameter->setType($namespace->resolveName($enumType));
                }

                if ($type === Types::Array) {
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

                    $parameter->setType('array')
                        ->addComment(
                            sprintf(
                                '@var %s%s $%s',
                                $arrayType->docType,
                                $parameter->isNullable() ? '|null' : '',
                                $parameter->getName()
                            )
                        );

                    $imports->addImport(...$arrayType->imports);
                }

                if ($type === Types::OneOf) {
                    $oneOfType = $this->transformOneOf(
                        $configuration,
                        $openApi,
                        $name,
                        $propertyName,
                        $property->oneOf,
                        $namespace,
                        $imports
                    );

                    if ($oneOfType->containsType('null')) {
                        $parameter->setNullable();
                        $oneOfType = $oneOfType->removeType('null');
                    }

                    $parameter->setType($oneOfType->nativeType());

                    if ($oneOfType->requiresPhpDoc()) {
                        $parameter->addComment(
                            sprintf(
                                '@var %s%s $%s',
                                $oneOfType->phpDocType(),
                                $parameter->isNullable() ? '|null' : '',
                                $parameter->getName()
                            )
                        );
                    }
                }
            }
        }

        // $schema->additionalProperties === true could not be handled because this is the default.
        // Has the benefit that all objects can be safely typed.
        if ($schemaType === Types::Object && (
            $schema->additionalProperties instanceof Schema
                || $schema->additionalProperties instanceof Reference
        )) {
            $dictionarySchema = $schema->additionalProperties;

            if ($dictionarySchema instanceof Reference) {
                $dictionarySchema = $this->referenceResolver->resolve($openApi, $dictionarySchema)
                    ->schema;
            }

            $dictionaryType = $this->resolveDictionaryType(
                $openApi,
                $dictionarySchema,
                $configuration,
                $name,
                $namespace,
                $imports
            );

            if ($dictionaryType instanceof ArrayType) {
                $imports->addImport(...$dictionaryType->imports);
            }

            $this->dictionaryResolver->resolve($namespace, $name, $class, $dictionaryType);
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

            $this->arrayObjectResolver->resolve($class, $constructor, $arrayType, $imports, $namespace);
        }

        if ($schemaType === Types::Enum) {
            $namespace->removeClass($name);
            $this->transformEnum($name, '', $schema, $namespace);
        }

        $this->serializableResolver->resolve($configuration, $openApi, $schema, $namespace, $class, $constructor);

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
                        return $this->referenceResolver->resolve($openApi, $schema)
                            ->schema;
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

        // @phpstan-ignore-next-line
        $enumVarNames = isset($schema->{'x-enum-varnames'}) ? $schema->{'x-enum-varnames'} : null;
        // @phpstan-ignore-next-line
        $enumVarDescriptions = isset($schema->{'x-enum-descriptions'}) ? $schema->{'x-enum-descriptions'} : null;

        if (is_array($enumVarNames) && count($schema->enum) !== count($enumVarNames)) {
            throw new InvalidEnumSchema($enumName, 'x-enum-varnames count does not match enum count');
        }

        if (is_array($enumVarDescriptions) && count($schema->enum) !== count($enumVarDescriptions)) {
            throw new InvalidEnumSchema($enumName, 'x-enum-descriptions count does not match enum count');
        }

        foreach ($schema->enum as $index => $enumValue) {
            if (! is_string($enumValue) && ! is_int($enumValue)) {
                throw new InvalidArgumentException(sprintf(
                    'Enum value must be string or integer, got %s',
                    gettype($enumValue)
                ));
            }

            $enumCaseName = match ($enum->getType()) {
                'int' => sprintf('Value%u', (int) $enumValue),
                default => ucfirst((string) $enumValue),
            };

            if (is_array($enumVarNames)) {
                $varName = $enumVarNames[$index];
                if (! is_string($varName)) {
                    throw new InvalidArgumentException(sprintf(
                        'Enum var name at index %d must be a string, got %s',
                        $index,
                        gettype($varName)
                    ));
                }
                $enumCaseName = $varName;
            }

            if (! Helpers::isIdentifier($enumCaseName)) {
                $enumCaseNameParts = preg_split('/[^A-z0-9]+/', $enumCaseName);
                if (! is_array($enumCaseNameParts)) {
                    throw new InvalidArgumentException(
                        sprintf('Value %s could not be converted to a enum name.', $enumValue)
                    );
                }

                $enumCaseName = join(array_map(ucfirst(...), $enumCaseNameParts));

                if (! Helpers::isIdentifier($enumCaseName)) {
                    throw new InvalidArgumentException(
                        sprintf('Value %s could not be converted to a enum name.', $enumValue)
                    );
                }
            }

            $enumCase = $enum->addCase($enumCaseName, $enumValue);

            if (is_array($enumVarDescriptions)) {
                $description = $enumVarDescriptions[$index];
                if (! is_string($description)) {
                    throw new InvalidArgumentException(sprintf(
                        'Enum description at index %d must be a string, got %s',
                        $index,
                        gettype($description)
                    ));
                }
                $enumCase->addComment($description);
            }
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
    ): ArrayType {
        $itemsSchema = $schema->items;
        if ($itemsSchema === null) {
            throw new UnresolvedArrayTypeException('missing type');
        }

        try {
            $arrayType = $this->typeResolver->resolve($openApi, $itemsSchema);
        } catch (InvalidArgumentException $exception) {
            throw new UnresolvedArrayTypeException('unknown type');
        }

        if ($arrayType instanceof ScalarType) {
            $arrayType = $arrayType->name;
        }

        if ($arrayType instanceof OneOfReference) {
            $itemsSchema = $arrayType->schema;
            $arrayType = Types::OneOf;
        }

        if ($arrayType === Types::Object) {
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

        if ($arrayType === Types::Enum) {
            $arrayType = $namespace->resolveName(
                $this->transformEnum($parentName, $propertyName, $itemsSchema, $namespace)
            );
        }

        if ($arrayType === Types::OneOf && is_array($itemsSchema->oneOf)) {
            $oneOfArrayType = $this->transformOneOf(
                $configuration,
                $openApi,
                $parentName,
                $propertyName,
                $itemsSchema->oneOf,
                $namespace,
                $imports
            );

            if ($oneOfArrayType->containsType(DateTimeInterface::class)) {
                throw new UnsupportedTypeForArrayException('date or datetime in oneOf');
            }

            return new ArrayType($oneOfArrayType, $nullable, sprintf('array<%s>', $oneOfArrayType->phpDocType()));
        }

        if (in_array($arrayType, [Types::Date, Types::DateTime], true)) {
            return new ArrayType(
                DateTimeInterface::class,
                $nullable,
                sprintf('array<%s>', DateTimeInterface::class),
                [DateTimeInterface::class]
            );
        }

        if ($arrayType === Types::Array) {
            $innerArrayType = $this->resolveArrayType(
                $configuration,
                $openApi,
                $parentName,
                $propertyName,
                $itemsSchema->nullable ?? false,
                $itemsSchema,
                $namespace,
                $imports
            );

            if ($innerArrayType->type === DateTimeInterface::class) {
                throw new UnsupportedTypeForArrayException('date', 'This is not possible in a nested array.');
            }

            return new ArrayType('array', $nullable, sprintf('array<%s>', $innerArrayType->docType));
        }

        if ($arrayType instanceof Types) {
            throw new UnresolvedArrayTypeException($arrayType->value);
        }

        if ($arrayType instanceof ClassReference) {
            return new ArrayType($arrayType, $nullable, sprintf('%s[]', $arrayType->name), [$arrayType->name]);
        }

        return new ArrayType($arrayType, $nullable, sprintf('%s[]', $namespace->simplifyName($arrayType)));
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
    ): OneOfType {
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
                    Types::Null => 'null',
                    Types::DateTime, Types::Date => $configuration->dateTimeAsObject ? DateTimeInterface::class : 'string',
                    Types::Array => $this->resolveArrayType(
                        $configuration,
                        $openApi,
                        $parentName,
                        $propertyName,
                        false,
                        $oneOfElement,
                        $namespace,
                        $imports
                    ),
                    Types::AllOf, Types::OneOf, Types::AnyOf => throw new UnsupportedTypeForOneOfException(
                        $resolvedType->value
                    ),
                    default => $resolvedType,
                };
            }

            if ($oneOfElement instanceof Reference) {
                $reference = $this->typeResolver->resolve($openApi, $oneOfElement);

                if ($reference instanceof OneOfReference) {
                    $resolvedTypes = array_merge(
                        $resolvedTypes,
                        $this->transformOneOf(
                            $configuration,
                            $openApi,
                            $parentName,
                            $propertyName,
                            $reference->schema->oneOf,
                            $namespace,
                            $imports
                        )->types
                    );
                }

                if ($reference instanceof ScalarType) {
                    $resolvedTypes[] = $reference->name;
                }

                if ($reference instanceof ClassReference) {
                    $imports->addImport($reference->name);
                    $resolvedTypes[] = $reference->name;
                }
            }
        }

        return new OneOfType($resolvedTypes);
    }

    private function resolveDictionaryType(
        OpenApi $openApi,
        Schema $dictionarySchema,
        Configuration $configuration,
        string $name,
        PhpNamespace $namespace,
        Imports $imports
    ): string|ArrayType|OneOfType {
        $dictionaryType = $this->typeResolver->resolve($openApi, $dictionarySchema);

        return match ($dictionaryType) {
            Types::Null, Types::AnyOf =>
            throw new UnsupportedTypeForDictionaryException($dictionaryType->value),
            Types::Array =>
            $this->resolveArrayType(
                $configuration,
                $openApi,
                $name,
                'value',
                false,
                $dictionarySchema,
                $namespace,
                $imports
            ),
            Types::AllOf => $this->transform(
                $configuration,
                $openApi,
                sprintf('%sDictionaryValue', $name),
                $dictionarySchema,
                $namespace,
                $imports
            )->getName() ?: throw new UnsupportedTypeForDictionaryException(
                $dictionaryType->value,
                'Class name is null'
            ),
            Types::Date, Types::DateTime => DateTimeInterface::class,
            Types::OneOf => $this->transformOneOf(
                $configuration,
                $openApi,
                $name,
                'DictionaryValue',
                $dictionarySchema->oneOf,
                $namespace,
                $imports
            ),
            Types::Enum => $this->transformEnum($name, 'DictionaryValue', $dictionarySchema, $namespace),
            Types::Object => $this->transformInlineObject(
                $configuration,
                $openApi,
                $name,
                'DictionaryValue',
                $dictionarySchema,
                $namespace,
                $imports
            ),
            default => $dictionaryType,
        };
    }
}

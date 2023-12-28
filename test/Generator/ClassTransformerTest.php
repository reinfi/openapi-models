<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Generator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use DG\BypassFinals;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PromotedParameter;
use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Exception\UnresolvedArrayTypeException;
use Reinfi\OpenApiModels\Exception\UnsupportedTypeForOneOfException;
use Reinfi\OpenApiModels\Generator\ClassTransformer;
use Reinfi\OpenApiModels\Generator\PropertyResolver;
use Reinfi\OpenApiModels\Generator\ReferenceResolver;
use Reinfi\OpenApiModels\Generator\TypeResolver;
use Reinfi\OpenApiModels\Generator\Types;
use Reinfi\OpenApiModels\Model\SchemaWithName;

class ClassTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        BypassFinals::enable();
    }

    public function testItTransformsSchemaWithScalarProperties(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);

        $referenceResolver->expects($this->never())->method('resolve');

        $typeResolver->expects($this->exactly(2))->method('resolve')->with(
            $openApi,
            $this->isInstanceOf(Schema::class),
            $namespace
        )->willReturn('int', 'string');

        $transformer = new ClassTransformer($propertyResolver, $typeResolver, $referenceResolver);

        $schema = new Schema([
            'properties' => [
                'id' => [
                    'type' => 'number',
                ],
                'name' => [
                    'type' => 'string',
                ],
            ],
        ]);

        $classType = $transformer->transform($openApi, 'Test', $schema, $namespace);

        self::assertEquals('Test', $classType->getName());
        self::assertCount(1, $namespace->getClasses());
    }

    public function testItResolvesAllOfSchema(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);

        $referenceResolver->expects($this->once())->method('resolve')->with($openApi, $this->callback(
            static fn (Reference $reference): bool => $reference->getReference() === '#/components/schemas/Test2'
        ))->willReturn(
            new SchemaWithName(
                'Test2',
                new Schema([
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                        ],
                    ],
                ])
            )
        );

        $typeResolver->expects($this->exactly(2))->method('resolve')->with(
            $openApi,
            $this->isInstanceOf(Schema::class),
            $namespace
        )->willReturn('int', 'string');

        $transformer = new ClassTransformer($propertyResolver, $typeResolver, $referenceResolver);

        $schema = new Schema([
            'allOf' => [
                [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'number',
                        ],
                    ],
                ],
                [
                    '$ref' => '#/components/schemas/Test2',
                ],
            ],
        ]);

        $classType = $transformer->transform($openApi, 'Test', $schema, $namespace);

        self::assertEquals('Test', $classType->getName());
        self::assertCount(1, $namespace->getClasses());
    }

    public function testItResolvesInlineObject(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);

        $referenceResolver->expects($this->never())->method('resolve');

        $typeResolver->expects($this->exactly(2))->method('resolve')->with(
            $openApi,
            $this->isInstanceOf(Schema::class),
            $namespace
        )->willReturn(Types::Object, 'string');

        $transformer = new ClassTransformer($propertyResolver, $typeResolver, $referenceResolver);

        $schema = new Schema([
            'type' => 'object',
            'properties' => [
                'uuid' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ]);

        $classType = $transformer->transform($openApi, 'Test', $schema, $namespace);

        self::assertEquals('Test', $classType->getName());
        self::assertCount(2, $namespace->getClasses());
        self::assertArrayHasKey('TestUuid', $namespace->getClasses());
    }

    public function testItResolvesEnumOfStrings(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);

        $referenceResolver->expects($this->never())->method('resolve');

        $typeResolver->expects($this->once())->method('resolve')->with(
            $openApi,
            $this->isInstanceOf(Schema::class),
            $namespace
        )->willReturn(Types::Enum);

        $transformer = new ClassTransformer($propertyResolver, $typeResolver, $referenceResolver);

        $schema = new Schema([
            'properties' => [
                'state' => [
                    'type' => 'string',
                    'enum' => ['positive', 'negative'],
                ],
            ],
        ]);

        $classType = $transformer->transform($openApi, 'Test', $schema, $namespace);
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(2, $classes);
        self::assertArrayHasKey('TestState', $classes);

        $enum = $classes['TestState'];
        self::assertInstanceOf(EnumType::class, $enum);
        self::assertEquals('string', $enum->getType());
        self::assertCount(2, $enum->getCases());
        self::assertArrayHasKey('Positive', $enum->getCases());
        self::assertArrayHasKey('Negative', $enum->getCases());
        self::assertEquals('positive', $enum->getCases()['Positive']->getValue());
        self::assertEquals('negative', $enum->getCases()['Negative']->getValue());
    }

    public function testItResolvesEnumOfIntegers(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);

        $referenceResolver->expects($this->never())->method('resolve');

        $typeResolver->expects($this->once())->method('resolve')->with(
            $openApi,
            $this->isInstanceOf(Schema::class),
            $namespace
        )->willReturn(Types::Enum);

        $transformer = new ClassTransformer($propertyResolver, $typeResolver, $referenceResolver);

        $schema = new Schema([
            'properties' => [
                'state' => [
                    'type' => 'number',
                    'enum' => [1, 2],
                ],
            ],
        ]);

        $classType = $transformer->transform($openApi, 'Test', $schema, $namespace);
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(2, $classes);
        self::assertArrayHasKey('TestState', $classes);

        $enum = $classes['TestState'];
        self::assertInstanceOf(EnumType::class, $enum);
        self::assertEquals('int', $enum->getType());
        self::assertCount(2, $enum->getCases());
        self::assertArrayHasKey('Value1', $enum->getCases());
        self::assertArrayHasKey('Value2', $enum->getCases());
        self::assertEquals(1, $enum->getCases()['Value1']->getValue());
        self::assertEquals(2, $enum->getCases()['Value2']->getValue());
    }

    public function testItResolvesToDefaultArrayOfTypeIfItemsSchemaIsNull(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');
        $parameter = new PromotedParameter('values');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);

        $referenceResolver->expects($this->never())->method('resolve');

        $typeResolver->expects($this->once())->method('resolve')->with(
            $openApi,
            $this->isInstanceOf(Schema::class),
            $namespace
        )->willReturn(Types::Array);

        $propertyResolver->expects($this->once())->method('resolve')->willReturn($parameter);

        $transformer = new ClassTransformer($propertyResolver, $typeResolver, $referenceResolver);

        $schema = new Schema([
            'properties' => [
                'values' => [
                    'type' => 'array',
                ],
            ],
        ]);

        $classType = $transformer->transform($openApi, 'Test', $schema, $namespace);
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(1, $classes);
        self::assertEquals('array', $parameter->getType());
    }

    public function testItResolvesToDefaultArrayIfTypeOfItemsSchemaIsNullWithNUllableProperty(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');
        $parameter = new PromotedParameter('values');
        $parameter->setNullable();

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);

        $referenceResolver->expects($this->never())->method('resolve');

        $typeResolver->expects($this->once())->method('resolve')->with(
            $openApi,
            $this->isInstanceOf(Schema::class),
            $namespace
        )->willReturn(Types::Array);

        $propertyResolver->expects($this->once())->method('resolve')->willReturn($parameter);

        $transformer = new ClassTransformer($propertyResolver, $typeResolver, $referenceResolver);

        $schema = new Schema([
            'properties' => [
                'values' => [
                    'type' => 'array',
                    'nullable' => true,
                ],
            ],
        ]);

        $classType = $transformer->transform($openApi, 'Test', $schema, $namespace);
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(1, $classes);
        self::assertEquals('array|null', $parameter->getType());
    }

    public function testItThrowsExceptionIfTypeIsNotString(): void
    {
        self::expectException(UnresolvedArrayTypeException::class);
        self::expectExceptionMessage('Could not resolve array type, got type "anyOf"');

        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');
        $parameter = new PromotedParameter('values');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);

        $referenceResolver->expects($this->never())->method('resolve');

        $typeResolver->expects($this->exactly(2))->method('resolve')->with(
            $openApi,
            $this->isInstanceOf(Schema::class),
            $namespace
        )->willReturn(Types::Array, Types::AnyOf);

        $propertyResolver->expects($this->once())->method('resolve')->willReturn($parameter);

        $transformer = new ClassTransformer($propertyResolver, $typeResolver, $referenceResolver);

        $schema = new Schema([
            'properties' => [
                'values' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'enum' => ['A', 'B'],
                    ],
                ],
            ],
        ]);

        $classType = $transformer->transform($openApi, 'Test', $schema, $namespace);
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(1, $classes);
        self::assertEquals('array', $parameter->getType());
    }

    public function testItResolvesScalarArrayTypes(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');
        $parameter = new PromotedParameter('values');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);

        $referenceResolver->expects($this->never())->method('resolve');

        $typeResolver->expects($this->exactly(2))->method('resolve')->with(
            $openApi,
            $this->isInstanceOf(Schema::class),
            $namespace
        )->willReturn(Types::Array, 'string');

        $propertyResolver->expects($this->once())->method('resolve')->willReturn($parameter);

        $transformer = new ClassTransformer($propertyResolver, $typeResolver, $referenceResolver);

        $schema = new Schema([
            'properties' => [
                'values' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ]);

        $classType = $transformer->transform($openApi, 'Test', $schema, $namespace);
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(1, $classes);
        self::assertEquals('array', $parameter->getType());
        self::assertEquals('@var string[] $values', $parameter->getComment());
    }

    public function testItResolvesScalarArrayTypesWithNullableProperty(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');
        $parameter = new PromotedParameter('values');
        $parameter->setNullable();

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);

        $referenceResolver->expects($this->never())->method('resolve');

        $typeResolver->expects($this->exactly(2))->method('resolve')->with(
            $openApi,
            $this->isInstanceOf(Schema::class),
            $namespace
        )->willReturn(Types::Array, 'string');

        $propertyResolver->expects($this->once())->method('resolve')->willReturn($parameter);

        $transformer = new ClassTransformer($propertyResolver, $typeResolver, $referenceResolver);

        $schema = new Schema([
            'properties' => [
                'values' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'nullable' => true,
                    ],
                ],
            ],
        ]);

        $classType = $transformer->transform($openApi, 'Test', $schema, $namespace);
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(1, $classes);
        self::assertEquals('array', $parameter->getType());
        self::assertEquals('@var string[]|null $values', $parameter->getComment());
    }

    public function testItResolvesInlineObjectAsArrayType(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');
        $arrayParameter = new PromotedParameter('values');
        $objectParameter = new PromotedParameter('id');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);

        $referenceResolver->expects($this->never())->method('resolve');

        $typeResolver->expects($this->exactly(3))->method('resolve')->with(
            $openApi,
            $this->isInstanceOf(Schema::class),
            $namespace
        )->willReturn(Types::Array, Types::Object, 'string');

        $propertyResolver->expects($this->exactly(2))->method('resolve')->willReturn($arrayParameter, $objectParameter);

        $transformer = new ClassTransformer($propertyResolver, $typeResolver, $referenceResolver);

        $schema = new Schema([
            'properties' => [
                'values' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $classType = $transformer->transform($openApi, 'Test', $schema, $namespace);
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(2, $classes);
        self::assertEquals('array', $arrayParameter->getType());
        self::assertEquals('@var TestValues[] $values', $arrayParameter->getComment());
        self::assertArrayHasKey('TestValues', $classes);
    }

    public function testItResolvesEnumAsArrayType(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');
        $arrayParameter = new PromotedParameter('states');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);

        $referenceResolver->expects($this->never())->method('resolve');

        $typeResolver->expects($this->exactly(2))->method('resolve')->with(
            $openApi,
            $this->isInstanceOf(Schema::class),
            $namespace
        )->willReturn(Types::Array, Types::Enum);

        $propertyResolver->expects($this->once())->method('resolve')->willReturn($arrayParameter);

        $transformer = new ClassTransformer($propertyResolver, $typeResolver, $referenceResolver);

        $schema = new Schema([
            'properties' => [
                'states' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'enum' => ['positive', 'negative'],
                    ],
                ],
            ],
        ]);

        $classType = $transformer->transform($openApi, 'Test', $schema, $namespace);
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(2, $classes);
        self::assertEquals('array', $arrayParameter->getType());
        self::assertEquals('@var TestStates[] $states', $arrayParameter->getComment());
        self::assertArrayHasKey('TestStates', $classes);
        self::assertInstanceOf(EnumType::class, $classes['TestStates']);
    }

    public function testItResolvesReferenceAsArrayType(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');
        $parameter = new PromotedParameter('values');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);

        $referenceResolver->expects($this->never())->method('resolve');
        $typeResolver->expects($this->exactly(2))->method('resolve')->with(
            $openApi,
            $this->callback(function (Schema|Reference $schema): bool {
                if ($schema instanceof Reference) {
                    return $schema->getReference() === '#/components/schemas/Test2';
                }

                return true;
            }),
            $namespace
        )->willReturn(Types::Array, 'Test2');

        $propertyResolver->expects($this->once())->method('resolve')->willReturn($parameter);

        $transformer = new ClassTransformer($propertyResolver, $typeResolver, $referenceResolver);

        $schema = new Schema([
            'properties' => [
                'values' => [
                    'type' => 'array',
                    'items' => [
                        '$ref' => '#/components/schemas/Test2',
                    ],
                ],
            ],
        ]);

        $classType = $transformer->transform($openApi, 'Test', $schema, $namespace);
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(1, $classes);
        self::assertEquals('array', $parameter->getType());
        self::assertEquals('@var Test2[] $values', $parameter->getComment());
    }

    public function testItResolvesOneOfAsArrayType(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');
        $parameter = new PromotedParameter('values');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);

        $referenceResolver->expects($this->never())->method('resolve');
        $typeResolver->expects($this->exactly(4))->method('resolve')->with(
            $openApi,
            $this->callback(function (Schema|Reference $schema): bool {
                if ($schema instanceof Reference) {
                    return in_array(
                        $schema->getReference(),
                        ['#/components/schemas/Test1', '#/components/schemas/Test2'],
                        true
                    );
                }

                if (is_array($schema->oneOf)) {
                    return true;
                }

                return $schema->type === 'array';
            }),
            $namespace
        )->willReturn(Types::Array, Types::OneOf, 'Test1', 'Test2');

        $propertyResolver->expects($this->once())->method('resolve')->willReturn($parameter);

        $transformer = new ClassTransformer($propertyResolver, $typeResolver, $referenceResolver);

        $schema = new Schema([
            'properties' => [
                'values' => [
                    'type' => 'array',
                    'items' => [
                        'oneOf' => [
                            [
                                '$ref' => '#/components/schemas/Test1',
                            ],
                            [
                                '$ref' => '#/components/schemas/Test2',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $classType = $transformer->transform($openApi, 'Test', $schema, $namespace);
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(1, $classes);
        self::assertEquals('array', $parameter->getType());
        self::assertEquals('@var array<Test1|Test2> $values', $parameter->getComment());
    }

    public function testItResolvesOneOfAsArrayTypeWithNullableProperty(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');
        $parameter = new PromotedParameter('values');
        $parameter->setNullable();

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);

        $referenceResolver->expects($this->never())->method('resolve');
        $typeResolver->expects($this->exactly(4))->method('resolve')->with(
            $openApi,
            $this->callback(function (Schema|Reference $schema): bool {
                if ($schema instanceof Reference) {
                    return in_array(
                        $schema->getReference(),
                        ['#/components/schemas/Test1', '#/components/schemas/Test2'],
                        true
                    );
                }

                if (is_array($schema->oneOf)) {
                    return true;
                }

                return $schema->type === 'array';
            }),
            $namespace
        )->willReturn(Types::Array, Types::OneOf, 'Test1', 'Test2');

        $propertyResolver->expects($this->once())->method('resolve')->willReturn($parameter);

        $transformer = new ClassTransformer($propertyResolver, $typeResolver, $referenceResolver);

        $schema = new Schema([
            'properties' => [
                'values' => [
                    'type' => 'array',
                    'items' => [
                        'oneOf' => [
                            [
                                '$ref' => '#/components/schemas/Test1',
                            ],
                            [
                                '$ref' => '#/components/schemas/Test2',
                            ],
                        ],
                        'nullable' => true,
                    ],
                ],
            ],
        ]);

        $classType = $transformer->transform($openApi, 'Test', $schema, $namespace);
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(1, $classes);
        self::assertEquals('array', $parameter->getType());
        self::assertEquals('@var array<Test1|Test2>|null $values', $parameter->getComment());
    }

    public function testItResolvesOneOf(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');
        $referenceParameter = new PromotedParameter('reference');
        $idParameter = new PromotedParameter('id');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);

        $referenceResolver->expects($this->never())->method('resolve');
        $typeResolver->expects($this->exactly(4))->method('resolve')->with(
            $openApi,
            $this->callback(function (Schema|Reference $schema): bool {
                if ($schema instanceof Reference) {
                    return $schema->getReference() === '#/components/schemas/Test2';
                }

                if (is_array($schema->oneOf) && count($schema->oneOf) === 2) {
                    return true;
                }

                return in_array($schema->type, ['object', 'string'], true);
            }),
            $namespace
        )->willReturn(Types::OneOf, Types::Object, 'string', 'Test2');

        $propertyResolver->expects($this->exactly(2))->method('resolve')->willReturn($referenceParameter, $idParameter);

        $transformer = new ClassTransformer($propertyResolver, $typeResolver, $referenceResolver);

        $schema = new Schema([
            'properties' => [
                'reference' => [
                    'oneOf' => [
                        [
                            'type' => 'object',
                            'properties' => [
                                'id' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                        [
                            '$ref' => '#/components/schemas/Test2',
                        ],
                    ],
                ],
            ],
        ]);

        $classType = $transformer->transform($openApi, 'Test', $schema, $namespace);
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(2, $classes);
        self::assertArrayHasKey('Test', $classes);
        self::assertArrayHasKey('TestReference1', $classes);
        self::assertEquals('TestReference1|Test2', $referenceParameter->getType());
    }

    public function testItThrowsExceptionIfOneOfContainerUnsupportedType(): void
    {
        self::expectException(UnsupportedTypeForOneOfException::class);
        self::expectExceptionMessage('Type "array" is currently not supported for oneOf definition');

        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');
        $referenceParameter = new PromotedParameter('reference');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);

        $referenceResolver->expects($this->never())->method('resolve');
        $typeResolver->expects($this->exactly(2))->method('resolve')->with(
            $openApi,
            $this->callback(function (Schema $schema): bool {
                if (is_array($schema->oneOf) && count($schema->oneOf) === 2) {
                    return true;
                }

                return $schema->type === 'array';
            }),
            $namespace
        )->willReturn(Types::OneOf, Types::Array);

        $propertyResolver->expects($this->once())->method('resolve')->willReturn($referenceParameter);

        $transformer = new ClassTransformer($propertyResolver, $typeResolver, $referenceResolver);

        $schema = new Schema([
            'properties' => [
                'reference' => [
                    'oneOf' => [
                        [
                            'type' => 'array',
                        ],
                        [
                            '$ref' => '#/components/schemas/Test2',
                        ],
                    ],
                ],
            ],
        ]);

        $transformer->transform($openApi, 'Test', $schema, $namespace);
    }
}
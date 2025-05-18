<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Generator;

use DateTimeInterface;
use DG\BypassFinals;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PromotedParameter;
use openapiphp\openapi\spec\OpenApi;
use openapiphp\openapi\spec\Reference;
use openapiphp\openapi\spec\Schema;
use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Exception\UnresolvedArrayTypeException;
use Reinfi\OpenApiModels\Exception\UnsupportedTypeForArrayException;
use Reinfi\OpenApiModels\Generator\AllOfPropertySchemaResolver;
use Reinfi\OpenApiModels\Generator\ArrayObjectResolver;
use Reinfi\OpenApiModels\Generator\ClassReference;
use Reinfi\OpenApiModels\Generator\ClassTransformer;
use Reinfi\OpenApiModels\Generator\DictionaryResolver;
use Reinfi\OpenApiModels\Generator\OpenApiType;
use Reinfi\OpenApiModels\Generator\PropertyResolver;
use Reinfi\OpenApiModels\Generator\ReferenceResolver;
use Reinfi\OpenApiModels\Generator\TypeResolver;
use Reinfi\OpenApiModels\Generator\Types;
use Reinfi\OpenApiModels\Model\AllOfType;
use Reinfi\OpenApiModels\Model\ArrayType;
use Reinfi\OpenApiModels\Model\Imports;
use Reinfi\OpenApiModels\Model\ScalarType;
use Reinfi\OpenApiModels\Model\SchemaWithName;
use Reinfi\OpenApiModels\Serialization\SerializableResolver;

class ClassTransformerTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        BypassFinals::enable(bypassReadOnly: false);

        $this->configuration = new Configuration([], '', '');
    }

    public function testItTransformsReference(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->once())
            ->method('resolve')
            ->with(
                $openApi,
                self::isInstanceOf(Reference::class),
            )->willReturn(new ClassReference(OpenApiType::Schemas, 'Test2'));

        $arrayObjectResolver->expects($this->never())
            ->method('resolve');

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver
        );

        $schema = new Reference([
            '$ref' => '#/components/schemas/Test2',
        ]);

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );

        self::assertEquals('Test', $classType->getName());
        self::assertCount(1, $namespace->getClasses());
        self::assertEquals('Test2', $classType->getExtends());
    }

    public function testItDoesNotTransformScalarProperty(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->once())
            ->method('resolve')
            ->with($openApi, self::isInstanceOf(Schema::class))->willReturn('string');

        $arrayObjectResolver->expects($this->never())
            ->method('resolve');

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $schema = new Schema([
            'type' => 'string',
        ]);

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );

        self::assertCount(0, $namespace->getClasses());
    }

    public function testItDoesNotTransformDateProperty(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->once())
            ->method('resolve')
            ->with($openApi, self::isInstanceOf(Schema::class))->willReturn(Types::Date);

        $arrayObjectResolver->expects($this->never())
            ->method('resolve');

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $schema = new Schema([
            'type' => 'string',
            'format' => 'date',
        ]);

        $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );

        self::assertCount(0, $namespace->getClasses());
    }

    public function testItSetsDescription(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $schema = new Schema([
            'description' => 'test',
        ]);

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );

        self::assertEquals('Test', $classType->getName());
        self::assertEquals('test', $classType->getComment());
    }

    public function testItDoesNotSetDescriptionIfEmpty(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $schema = new Schema([
            'description' => '',
        ]);

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );

        self::assertEquals('Test', $classType->getName());
        self::assertNull($classType->getComment());
    }

    public function testItDoesNotFailIfRequiredIsNotArray(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $propertyResolver->method('resolve')
            ->with(self::isInstanceOf(Method::class), 'id', self::isInstanceOf(Schema::class), false, 'int')
            ->willReturn(new PromotedParameter('test'));

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->exactly(2))
            ->method('resolve')
            ->with($openApi, self::isInstanceOf(Schema::class))->willReturn(Types::Object, 'int');

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $schema = new Schema([
            'required' => true,
            'properties' => [
                'id' => [
                    'type' => 'number',
                ],
            ],
        ]);

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );

        self::assertEquals('Test', $classType->getName());
        self::assertCount(1, $namespace->getClasses());
    }

    public function testItTransformsSchemaWithScalarProperties(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $propertyResolver->method('resolve')
            ->willReturn(new PromotedParameter('test'));

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->exactly(3))
            ->method('resolve')
            ->with($openApi, self::isInstanceOf(Schema::class))->willReturn(Types::Object, 'int', 'string');

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

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

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );

        self::assertEquals('Test', $classType->getName());
        self::assertCount(1, $namespace->getClasses());
    }

    public function testItTransformsSchemaWithDateTimePropertiesAsString(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');

        $dateProperty = new PromotedParameter('date');
        $dateTimeProperty = new PromotedParameter('dateTime');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->exactly(3))
            ->method('resolve')
            ->with($openApi, self::isInstanceOf(Schema::class))->willReturn(
                Types::Object,
                Types::DateTime,
                Types::Date
            );

        $propertyResolver->expects($this->exactly(2))
            ->method('resolve')
            ->willReturn($dateProperty, $dateTimeProperty);

        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $schema = new Schema([
            'properties' => [
                'date' => [
                    'type' => 'string',
                    'format' => 'date',
                ],
                'dateTime' => [
                    'type' => 'string',
                    'format' => 'date-time',
                ],
            ],
        ]);

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );

        self::assertEquals('Test', $classType->getName());
        self::assertCount(1, $namespace->getClasses());
        self::assertEquals('string', $dateProperty->getType());
        self::assertEquals('string', $dateTimeProperty->getType());
    }

    public function testItTransformsSchemaWithDateTimePropertiesAsDateTimeInterface(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('Api');
        $imports = new Imports($namespace);
        $configuration = new Configuration([], '', '', false, true);

        $dateProperty = new PromotedParameter('date');
        $dateTimeProperty = new PromotedParameter('dateTime');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->exactly(3))
            ->method('resolve')
            ->with($openApi, self::isInstanceOf(Schema::class))->willReturn(
                Types::Object,
                Types::Date,
                Types::DateTime
            );

        $propertyResolver->expects($this->exactly(2))
            ->method('resolve')
            ->willReturn($dateProperty, $dateTimeProperty);

        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $schema = new Schema([
            'properties' => [
                'date' => [
                    'type' => 'string',
                    'format' => 'date',
                ],
                'dateTime' => [
                    'type' => 'string',
                    'format' => 'date-time',
                ],
            ],
        ]);

        $classType = $transformer->transform($configuration, $openApi, 'Test', $schema, $namespace, $imports);

        self::assertEquals('Test', $classType->getName());
        self::assertCount(1, $namespace->getClasses());
        self::assertEquals(DateTimeInterface::class, $dateProperty->getType());
        self::assertEquals(DateTimeInterface::class, $dateTimeProperty->getType());

        $imports->copyImports();

        self::assertContains(DateTimeInterface::class, $namespace->getUses());
    }

    public function testItResolvesAllOfSchema(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $propertyResolver->method('resolve')
            ->willReturn(new PromotedParameter('test'));

        $referenceResolver->expects($this->once())
            ->method('resolve')
            ->with(
                $openApi,
                self::callback(
                    static fn (
                        Reference $reference
                    ): bool => $reference->getReference() === '#/components/schemas/Test2'
                )
            )->willReturn(
                new SchemaWithName(
                    OpenApiType::Schemas,
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

        $typeResolver->expects($this->exactly(3))
            ->method('resolve')
            ->with($openApi, self::isInstanceOf(Schema::class))->willReturn(Types::Object, 'int', 'string');

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

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

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );

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

        $propertyResolver->method('resolve')
            ->willReturn(new PromotedParameter('test'));

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->exactly(4))
            ->method('resolve')
            ->with(
                $openApi,
                self::isInstanceOf(Schema::class),
            )->willReturn(Types::Object, Types::Object, Types::Object, 'string');

        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

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

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );

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
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $propertyResolver->method('resolve')
            ->willReturn(new PromotedParameter('test'));

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->exactly(2))
            ->method('resolve')
            ->with($openApi, self::isInstanceOf(Schema::class))->willReturn(Types::Object, Types::Enum);

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $schema = new Schema([
            'properties' => [
                'state' => [
                    'type' => 'string',
                    'enum' => ['positive', 'negative'],
                ],
            ],
        ]);

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );
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

    public function testItResolvesEnumWithNames(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $propertyResolver->method('resolve')
            ->willReturn(new PromotedParameter('test'));

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->exactly(2))
            ->method('resolve')
            ->with($openApi, self::isInstanceOf(Schema::class))->willReturn(Types::Object, Types::Enum);

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $schema = new Schema([
            'properties' => [
                'state' => [
                    'type' => 'string',
                    'enum' => ['StringName', 'IntegerName'],
                    'x-enum-varnames' => ['String', '1'],
                ],
            ],
        ]);

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(2, $classes);
        self::assertArrayHasKey('TestState', $classes);

        $enum = $classes['TestState'];
        self::assertInstanceOf(EnumType::class, $enum);
        self::assertEquals('string', $enum->getType());
        self::assertCount(2, $enum->getCases());
        self::assertArrayHasKey('String', $enum->getCases());
        self::assertArrayHasKey('One', $enum->getCases());
        self::assertEquals('StringName', $enum->getCases()['String']->getValue());
        self::assertEquals('IntegerName', $enum->getCases()['One']->getValue());
    }

    public function testItResolvesEnumOfIntegers(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $propertyResolver->method('resolve')
            ->willReturn(new PromotedParameter('test'));

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->exactly(2))
            ->method('resolve')
            ->with($openApi, self::isInstanceOf(Schema::class))->willReturn(Types::Object, Types::Enum);

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $schema = new Schema([
            'properties' => [
                'state' => [
                    'type' => 'number',
                    'enum' => [1, 2],
                ],
            ],
        ]);

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(2, $classes);
        self::assertArrayHasKey('TestState', $classes);

        $enum = $classes['TestState'];
        self::assertInstanceOf(EnumType::class, $enum);
        self::assertEquals('int', $enum->getType());
        self::assertCount(2, $enum->getCases());
        self::assertArrayHasKey('One', $enum->getCases());
        self::assertArrayHasKey('Two', $enum->getCases());
        self::assertEquals(1, $enum->getCases()['One']->getValue());
        self::assertEquals(2, $enum->getCases()['Two']->getValue());
    }

    public function testItThrowsExceptionIfItemsSchemaIsNull(): void
    {
        self::expectException(UnresolvedArrayTypeException::class);
        self::expectExceptionMessage('Could not resolve array type, got type "missing type"');

        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');
        $parameter = new PromotedParameter('values');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->exactly(2))
            ->method('resolve')
            ->with($openApi, self::isInstanceOf(Schema::class))->willReturn(Types::Object, Types::Array);

        $propertyResolver->expects($this->once())
            ->method('resolve')
            ->willReturn($parameter);

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $schema = new Schema([
            'properties' => [
                'values' => [
                    'type' => 'array',
                ],
            ],
        ]);

        $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );
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
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->exactly(3))
            ->method('resolve')
            ->with($openApi, self::isInstanceOf(Schema::class))
            ->willReturn(Types::Object, Types::Array, Types::AnyOf);

        $propertyResolver->expects($this->once())
            ->method('resolve')
            ->willReturn($parameter);

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

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

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );
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
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->exactly(3))
            ->method('resolve')
            ->with($openApi, self::isInstanceOf(Schema::class))
            ->willReturn(Types::Object, Types::Array, 'string');

        $propertyResolver->expects($this->once())
            ->method('resolve')
            ->willReturn($parameter);

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

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

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(1, $classes);
        self::assertEquals('array', $parameter->getType());
        self::assertEquals('@var array<string> $values', $parameter->getComment());
    }

    public function testItResolvesScalarArrayTypesAsReference(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');
        $parameter = new PromotedParameter('values');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->exactly(3))
            ->method('resolve')
            ->with(
                $openApi,
                self::callback(
                    function (Schema|Reference $schema): bool {
                        if ($schema instanceof Reference) {
                            return $schema->getReference() === '#/components/schemas/Id';
                        }

                        return in_array($schema->type, ['object', 'array'], true);
                    }
                ),
            )->willReturn(Types::Object, Types::Array, new ScalarType('int', new Schema([])));

        $propertyResolver->expects($this->once())
            ->method('resolve')
            ->willReturn($parameter);

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $schema = new Schema([
            'type' => 'object',
            'properties' => [
                'values' => [
                    'type' => 'array',
                    'items' => [
                        '$ref' => '#/components/schemas/Id',
                    ],
                ],
            ],
        ]);

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(1, $classes);
        self::assertEquals('array', $parameter->getType());
        self::assertEquals('@var array<int> $values', $parameter->getComment());
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
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->exactly(3))
            ->method('resolve')
            ->with($openApi, self::isInstanceOf(Schema::class))
            ->willReturn(Types::Object, Types::Array, 'string');

        $propertyResolver->expects($this->once())
            ->method('resolve')
            ->willReturn($parameter);

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

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

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(1, $classes);
        self::assertEquals('array', $parameter->getType());
        self::assertEquals('@var array<string|null>|null $values', $parameter->getComment());
    }

    public function testItResolvesDateArrayType(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');
        $parameter = new PromotedParameter('dates');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->exactly(3))
            ->method('resolve')
            ->with($openApi, self::isInstanceOf(Schema::class))
            ->willReturn(Types::Object, Types::Array, Types::Date);

        $propertyResolver->expects($this->once())
            ->method('resolve')
            ->willReturn($parameter);

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $schema = new Schema([
            'properties' => [
                'dates' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'format' => 'date',
                    ],
                ],
            ],
        ]);

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(1, $classes);
        self::assertEquals('array', $parameter->getType());
        self::assertEquals('@var array<DateTimeInterface> $dates', $parameter->getComment());
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
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->exactly(5))
            ->method('resolve')
            ->with(
                $openApi,
                self::isInstanceOf(Schema::class),
            )->willReturn(Types::Object, Types::Array, Types::Object, Types::Object, 'string');

        $propertyResolver->expects($this->exactly(2))
            ->method('resolve')
            ->willReturn($arrayParameter, $objectParameter);

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $schema = new Schema([
            'type' => 'object',
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

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(2, $classes);
        self::assertEquals('array', $arrayParameter->getType());
        self::assertEquals('@var array<TestValues> $values', $arrayParameter->getComment());
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
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->exactly(3))
            ->method('resolve')
            ->with($openApi, self::isInstanceOf(Schema::class))
            ->willReturn(Types::Object, Types::Array, Types::Enum);

        $propertyResolver->expects($this->once())
            ->method('resolve')
            ->willReturn($arrayParameter);

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

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

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(2, $classes);
        self::assertEquals('array', $arrayParameter->getType());
        self::assertEquals('@var array<TestStates> $states', $arrayParameter->getComment());
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
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->exactly(3))
            ->method('resolve')
            ->with(
                $openApi,
                self::callback(function (Schema|Reference $schema): bool {
                    if ($schema instanceof Reference) {
                        return $schema->getReference() === '#/components/schemas/Test2';
                    }

                    return true;
                }),
            )->willReturn(Types::Object, Types::Array, 'Test2');

        $propertyResolver->expects($this->once())
            ->method('resolve')
            ->willReturn($parameter);

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

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

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(1, $classes);
        self::assertEquals('array', $parameter->getType());
        self::assertEquals('@var array<Test2> $values', $parameter->getComment());
    }

    public function testItResolvesOneOfAsArrayType(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');
        $parameter = new PromotedParameter('values');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->exactly(5))
            ->method('resolve')
            ->with(
                $openApi,
                self::callback(function (Schema|Reference $schema): bool {
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

                    return $schema->type === 'array' || $schema->type === 'object';
                }),
            )->willReturn(
                Types::Object,
                Types::Array,
                Types::OneOf,
                new ClassReference(OpenApiType::Schemas, 'Test1'),
                new ClassReference(OpenApiType::Schemas, 'Test2')
            );

        $propertyResolver->expects($this->once())
            ->method('resolve')
            ->willReturn($parameter);

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $schema = new Schema([
            'type' => 'object',
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

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );
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
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->exactly(5))
            ->method('resolve')
            ->with(
                $openApi,
                self::callback(function (Schema|Reference $schema): bool {
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

                    return $schema->type === 'array' || $schema->type === 'object';
                }),
            )->willReturn(
                Types::Object,
                Types::Array,
                Types::OneOf,
                new ClassReference(OpenApiType::Schemas, 'Test1'),
                new ClassReference(OpenApiType::Schemas, 'Test2')
            );

        $propertyResolver->expects($this->once())
            ->method('resolve')
            ->willReturn($parameter);

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $schema = new Schema([
            'type' => 'object',
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

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(1, $classes);
        self::assertEquals('array', $parameter->getType());
        self::assertEquals('@var array<Test1|Test2>|null $values', $parameter->getComment());
    }

    public function testItThrowsExceptionIfOneOfContainerDate(): void
    {
        self::expectException(UnsupportedTypeForArrayException::class);
        self::expectExceptionMessage(
            'Type "date or datetime in oneOf" is currently not supported for array definition'
        );

        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');
        $parameter = new PromotedParameter('values');
        $configuration = new Configuration([], '', '', false, true);

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');
        $typeResolver->expects($this->exactly(5))
            ->method('resolve')
            ->with(
                $openApi,
                self::callback(function (Schema|Reference $schema): bool {
                    if ($schema instanceof Reference) {
                        return $schema->getReference() === '#/components/schemas/Test1';
                    }

                    if (is_array($schema->oneOf)) {
                        return true;
                    }

                    return $schema->type === 'object' || $schema->type === 'array' || ($schema->type === 'string' && $schema->format === 'date');
                }),
            )->willReturn(
                Types::Object,
                Types::Array,
                Types::OneOf,
                new ClassReference(OpenApiType::Schemas, 'Test1'),
                Types::Date
            );

        $propertyResolver->expects($this->once())
            ->method('resolve')
            ->willReturn($parameter);

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $schema = new Schema([
            'type' => 'object',
            'properties' => [
                'values' => [
                    'type' => 'array',
                    'items' => [
                        'oneOf' => [
                            [
                                '$ref' => '#/components/schemas/Test1',
                            ],
                            [
                                'type' => 'string',
                                'format' => 'date',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $transformer->transform($configuration, $openApi, 'Test', $schema, $namespace, new Imports($namespace));
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
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->exactly(6))
            ->method('resolve')
            ->with(
                $openApi,
                self::callback(function (Schema|Reference $schema): bool {
                    if ($schema instanceof Reference) {
                        return $schema->getReference() === '#/components/schemas/Test2';
                    }

                    if (is_array($schema->oneOf) && count($schema->oneOf) === 2) {
                        return true;
                    }

                    return in_array($schema->type, ['object', 'string'], true);
                }),
            )->willReturn(
                Types::Object,
                Types::OneOf,
                Types::Object,
                Types::Object,
                'string',
                new ClassReference(OpenApiType::Schemas, 'Test2')
            );

        $propertyResolver->expects($this->exactly(2))
            ->method('resolve')
            ->willReturn($referenceParameter, $idParameter);

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $schema = new Schema([
            'type' => 'object',
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

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );
        $classes = $namespace->getClasses();

        self::assertEquals('Test', $classType->getName());
        self::assertCount(2, $classes);
        self::assertArrayHasKey('Test', $classes);
        self::assertArrayHasKey('TestReference1', $classes);
        self::assertEquals('TestReference1|Test2', $referenceParameter->getType());
    }

    public function testItResolvesToArrayObject(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->exactly(2))
            ->method('resolve')
            ->with(
                $openApi,
                self::callback(static fn (Schema $schema): bool => in_array(
                    $schema->type,
                    ['array', 'string'],
                    true
                )),
            )->willReturn(Types::Array, 'string');

        $propertyResolver->expects($this->never())
            ->method('resolve');

        $arrayObjectResolver->expects($this->once())
            ->method('resolve')
            ->with(
                self::isInstanceOf(ClassType::class),
                self::isInstanceOf(Method::class),
                self::callback(static fn (ArrayType $arrayType): bool => $arrayType->type === 'string'),
                self::isInstanceOf(Imports::class),
            );

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $schema = new Schema([
            'type' => 'array',
            'items' => [
                'type' => 'string',
            ],
        ]);

        $classType = $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );

        self::assertEquals('Test', $classType->getName());
    }

    public function testItResolvesToEnum(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->once())
            ->method('resolve')
            ->with(
                $openApi,
                self::callback(
                    static fn (Schema $schema): bool => $schema->type === 'string' && count($schema->enum) === 3
                ),
            )->willReturn(Types::Enum);

        $propertyResolver->expects($this->never())
            ->method('resolve');

        $arrayObjectResolver->expects($this->never())
            ->method('resolve');

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $schema = new Schema([
            'type' => 'string',
            'enum' => ['green', 'red', 'white'],
        ]);

        $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );

        $classes = $namespace->getClasses();

        self::assertCount(1, $classes);
        self::assertArrayHasKey('Test', $classes);

        $enum = $classes['Test'];
        self::assertInstanceOf(EnumType::class, $enum);
        self::assertCount(3, $enum->getCases());
    }

    public function testItSanitizesEnumName(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->once())
            ->method('resolve')
            ->with(
                $openApi,
                self::callback(
                    static fn (Schema $schema): bool => $schema->type === 'string' && count($schema->enum) === 2
                ),
            )->willReturn(Types::Enum);

        $propertyResolver->expects($this->never())
            ->method('resolve');

        $arrayObjectResolver->expects($this->never())
            ->method('resolve');

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $schema = new Schema([
            'type' => 'string',
            'enum' => ['status.ok', 'status.danger'],
        ]);

        $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );

        $classes = $namespace->getClasses();

        self::assertCount(1, $classes);
        self::assertArrayHasKey('Test', $classes);

        $enum = $classes['Test'];
        self::assertInstanceOf(EnumType::class, $enum);
        $enumCases = $enum->getCases();
        self::assertCount(2, $enumCases);
        self::assertArrayHasKey('StatusOk', $enumCases);
        self::assertArrayHasKey('StatusDanger', $enumCases);
    }

    public function testItResolvesAllOf(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');
        $parameter = new PromotedParameter('dollar');
        $propertySchema = new Schema([
            'type' => 'string',
        ]);

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $referenceResolver->expects($this->never())
            ->method('resolve');

        $typeResolver->expects($this->exactly(2))
            ->method('resolve')
            ->with(
                $openApi,
                self::callback(
                    static fn (
                        Schema $schema
                    ): bool => $schema->type === 'object' || (is_array(
                        $schema->allOf
                    ) && count($schema->allOf) === 2)
                ),
            )->willReturn(Types::Object, Types::AllOf);

        $propertyResolver->expects($this->once())
            ->method('resolve')
            ->with(self::isInstanceOf(Method::class), 'dollar', $propertySchema, false, 'string')
            ->willReturn($parameter);

        $arrayObjectResolver->expects($this->never())
            ->method('resolve');

        $allOfPropertySchemaResolver->expects($this->once())
            ->method('resolve')
            ->with(
                $openApi,
                self::isInstanceOf(Schema::class),
                'dollar'
            )->willReturn(new AllOfType('string', $propertySchema));

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $schema = new Schema([
            'type' => 'object',
            'properties' => [
                'dollar' => [
                    'allOf' => [
                        $propertySchema,
                        [
                            'description' => 'Foo Bar',
                        ],
                    ],
                ],
            ],
        ]);

        $transformer->transform(
            $this->configuration,
            $openApi,
            'Test',
            $schema,
            $namespace,
            new Imports($namespace)
        );

        $classes = $namespace->getClasses();

        self::assertCount(1, $classes);
        self::assertArrayHasKey('Test', $classes);
    }

    public function testItResolvesAdditionalProperties(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');

        $schema = new Schema([
            'type' => 'object',
            'additionalProperties' => [
                'type' => 'string',
            ],
        ]);

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $typeResolver->method('resolve')
            ->willReturn(Types::Object, 'string');

        $dictionaryResolver->expects($this->once())
            ->method('resolve')
            ->with($namespace, 'Test', self::isInstanceOf(ClassType::class), 'string');

        $serializableResolver->expects($this->once())
            ->method('resolve')
            ->with(
                $this->configuration,
                $openApi,
                $schema,
                $namespace,
                self::isInstanceOf(ClassType::class),
                self::isInstanceOf(Method::class)
            );

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $transformer->transform($this->configuration, $openApi, 'Test', $schema, $namespace, new Imports($namespace));
    }

    public function testItCallsSerialization(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');

        $schema = new Schema([
            'type' => 'object',
            'properties' => [
                'date' => [
                    'type' => 'string',
                    'format' => 'date',
                ],
            ],
        ]);

        $propertyResolver = $this->createMock(PropertyResolver::class);
        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $serializableResolver = $this->createMock(SerializableResolver::class);
        $arrayObjectResolver = $this->createMock(ArrayObjectResolver::class);
        $allOfPropertySchemaResolver = $this->createMock(AllOfPropertySchemaResolver::class);
        $dictionaryResolver = $this->createMock(DictionaryResolver::class);

        $typeResolver->method('resolve')
            ->willReturn(Types::Object, Types::Date);

        $propertyResolver->method('resolve')
            ->willReturn(new PromotedParameter('date'));

        $serializableResolver->expects($this->once())
            ->method('resolve')
            ->with(
                $this->configuration,
                $openApi,
                $schema,
                $namespace,
                self::isInstanceOf(ClassType::class),
                self::isInstanceOf(Method::class)
            );

        $transformer = new ClassTransformer(
            $propertyResolver,
            $typeResolver,
            $referenceResolver,
            $serializableResolver,
            $arrayObjectResolver,
            $allOfPropertySchemaResolver,
            $dictionaryResolver,
        );

        $transformer->transform($this->configuration, $openApi, 'Test', $schema, $namespace, new Imports($namespace));
    }
}

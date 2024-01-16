<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Generator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use DateTimeInterface;
use DG\BypassFinals;
use InvalidArgumentException;
use Nette\PhpGenerator\PhpNamespace;
use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Generator\ClassReference;
use Reinfi\OpenApiModels\Generator\NamespaceResolver;
use Reinfi\OpenApiModels\Generator\OpenApiType;
use Reinfi\OpenApiModels\Generator\ReferenceResolver;
use Reinfi\OpenApiModels\Generator\TypeResolver;
use Reinfi\OpenApiModels\Generator\Types;
use Reinfi\OpenApiModels\Model\OneOfReference;
use Reinfi\OpenApiModels\Model\ScalarType;
use Reinfi\OpenApiModels\Model\SchemaWithName;

class TypeResolverTest extends TestCase
{
    protected function setUp(): void
    {
        BypassFinals::enable();
    }

    public static function TypeDataProvider(): array
    {
        return [
            [
                'schema' => new Schema([
                    'type' => 'string',
                ]),
                'expectedType' => 'string',
            ],
            [
                'schema' => new Schema([
                    'type' => 'integer',
                ]),
                'expectedType' => 'int',
            ],
            [
                'schema' => new Schema([
                    'type' => 'boolean',
                ]),
                'expectedType' => 'bool',
            ],
            [
                'schema' => new Schema([
                    'type' => 'number',
                ]),
                'expectedType' => 'int',
            ],
            [
                'schema' => new Schema([
                    'type' => 'number',
                    'format' => 'double',
                ]),
                'expectedType' => 'float',
            ],
            [
                'schema' => new Schema([
                    'type' => 'number',
                    'format' => 'float',
                ]),
                'expectedType' => 'float',
            ],
            [
                'schema' => new Schema([
                    'type' => 'null',
                ]),
                'expectedType' => Types::Null,
            ],
            [
                'schema' => new Schema([
                    'type' => 'object',
                ]),
                'expectedType' => Types::Object,
            ],
            [
                'schema' => new Schema([
                    'properties' => [
                        'id' => [
                            'type' => 'string',
                        ],
                    ],
                ]),
                'expectedType' => Types::Object,
            ],
            [
                'schema' => new Schema([
                    'type' => 'array',
                ]),
                'expectedType' => Types::Array,
            ],
            [
                'schema' => new Schema([
                    'oneOf' => [[
                        '$ref' => '#/component/schemas/TestSchema',
                    ]],
                ]),
                'expectedType' => Types::OneOf,
            ],
            [
                'schema' => new Schema([
                    'type' => 'string',
                    'enum' => ['foo', 'bar'],
                ]),
                'expectedType' => Types::Enum,
            ],
            [
                'schema' => new Schema([
                    'type' => 'number',
                    'enum' => [1, 2],
                ]),
                'expectedType' => Types::Enum,
            ],
        ];
    }

    /**
     * @dataProvider TypeDataProvider
     */
    public function testItResolvesType(Schema $schema, string|Types $expectedType): void
    {
        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $referenceResolver->expects($this->never())
            ->method('resolve');

        $namespaceResolver = $this->createMock(NamespaceResolver::class);
        $namespaceResolver->expects($this->never())
            ->method('resolveNamespace');

        $resolver = new TypeResolver($referenceResolver, $namespaceResolver);

        $openApi = new OpenApi([]);

        self::assertEquals($expectedType, $resolver->resolve($openApi, $schema));
    }

    public function testItResolvesReference(): void
    {
        $openApi = new OpenApi([]);
        $reference = new Reference([
            '$ref' => '#/components/schemas/TestSchema',
        ]);

        $namespace = new PhpNamespace('');

        $schemaWithName = new SchemaWithName(OpenApiType::Schemas, 'TestSchema', new Schema([
            'type' => 'object',
        ]));

        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $referenceResolver->expects($this->once())
            ->method('resolve')
            ->with($openApi, $reference)
            ->willReturn($schemaWithName);

        $namespaceResolver = $this->createMock(NamespaceResolver::class);
        $namespaceResolver->expects($this->once())
            ->method('resolveNamespace')
            ->with(OpenApiType::Schemas)->willReturn($namespace);

        $resolver = new TypeResolver($referenceResolver, $namespaceResolver);

        $resolvedType = $resolver->resolve($openApi, $reference);

        self::assertInstanceOf(ClassReference::class, $resolvedType);
        self::assertEquals(OpenApiType::Schemas, $resolvedType->openApiType);
        self::assertEquals('TestSchema', $resolvedType->name);
    }

    public function testItResolvesReferenceToScalarProperty(): void
    {
        $openApi = new OpenApi([]);
        $reference = new Reference([
            '$ref' => '#/components/schemas/Id',
        ]);

        $schemaWithName = new SchemaWithName(OpenApiType::Schemas, 'Id', new Schema([
            'type' => 'integer',
        ]));

        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $referenceResolver->expects($this->once())
            ->method('resolve')
            ->with($openApi, $reference)
            ->willReturn($schemaWithName);

        $namespaceResolver = $this->createMock(NamespaceResolver::class);
        $namespaceResolver->expects($this->never())
            ->method('resolveNamespace');

        $resolver = new TypeResolver($referenceResolver, $namespaceResolver);

        $resolvedType = $resolver->resolve($openApi, $reference);

        self::assertInstanceOf(ScalarType::class, $resolvedType);
        self::assertEquals('int', $resolvedType->name);
    }

    public function testItResolvesReferenceToScalarDateProperty(): void
    {
        $openApi = new OpenApi([]);
        $reference = new Reference([
            '$ref' => '#/components/schemas/Date',
        ]);

        $schemaWithName = new SchemaWithName(OpenApiType::Schemas, 'Date', new Schema([
            'type' => 'string',
            'format' => 'date',
        ]));

        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $referenceResolver->expects($this->once())
            ->method('resolve')
            ->with($openApi, $reference)
            ->willReturn($schemaWithName);

        $namespaceResolver = $this->createMock(NamespaceResolver::class);
        $namespaceResolver->expects($this->never())
            ->method('resolveNamespace');

        $resolver = new TypeResolver($referenceResolver, $namespaceResolver);

        $resolvedType = $resolver->resolve($openApi, $reference);

        self::assertInstanceOf(ClassReference::class, $resolvedType);
        self::assertEquals(OpenApiType::Schemas, $resolvedType->openApiType);
        self::assertEquals(DateTimeInterface::class, $resolvedType->name);
    }

    public function testItResolvesReferenceToOneOfSchema(): void
    {
        $openApi = new OpenApi([]);
        $reference = new Reference([
            '$ref' => '#/components/schemas/TestOrNoTest',
        ]);

        $schemaWithName = new SchemaWithName(OpenApiType::Schemas, 'TestOrNoTest', new Schema([
            'oneOf' => [
                [
                    '$ref' => '#/components/schemas/Test',
                ],
                [
                    '$ref' => '#/components/schemas/NotTest',
                ],
            ],
        ]));

        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $referenceResolver->expects($this->once())
            ->method('resolve')
            ->with($openApi, $reference)
            ->willReturn($schemaWithName);

        $namespaceResolver = $this->createMock(NamespaceResolver::class);
        $namespaceResolver->expects($this->never())
            ->method('resolveNamespace');

        $resolver = new TypeResolver($referenceResolver, $namespaceResolver);

        $resolvedType = $resolver->resolve($openApi, $reference);

        self::assertInstanceOf(OneOfReference::class, $resolvedType);
        self::assertEquals($schemaWithName->schema, $resolvedType->schema);
    }

    public function testItThrowsExceptionIfUnknownType(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Not implemented type "unknown" found');

        $referenceResolver = $this->createMock(ReferenceResolver::class);
        $referenceResolver->expects($this->never())
            ->method('resolve');

        $namespaceResolver = $this->createMock(NamespaceResolver::class);
        $namespaceResolver->expects($this->never())
            ->method('resolveNamespace');

        $resolver = new TypeResolver($referenceResolver, $namespaceResolver);

        $openApi = new OpenApi([]);

        $schema = new Schema([
            'type' => 'unknown',
        ]);

        $resolver->resolve($openApi, $schema);
    }
}

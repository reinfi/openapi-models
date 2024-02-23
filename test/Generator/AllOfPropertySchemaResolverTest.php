<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Generator;

use DG\BypassFinals;
use openapiphp\openapi\spec\OpenApi;
use openapiphp\openapi\spec\Reference;
use openapiphp\openapi\spec\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Exception\InvalidAllOfException;
use Reinfi\OpenApiModels\Generator\AllOfPropertySchemaResolver;
use Reinfi\OpenApiModels\Generator\ClassReference;
use Reinfi\OpenApiModels\Generator\OpenApiType;
use Reinfi\OpenApiModels\Generator\ReferenceResolver;
use Reinfi\OpenApiModels\Generator\TypeResolver;
use Reinfi\OpenApiModels\Generator\Types;
use Reinfi\OpenApiModels\Model\AllOfType;
use Reinfi\OpenApiModels\Model\OneOfReference;
use Reinfi\OpenApiModels\Model\ScalarType;
use Reinfi\OpenApiModels\Model\SchemaWithName;

class AllOfPropertySchemaResolverTest extends TestCase
{
    protected function setUp(): void
    {
        BypassFinals::enable();
    }

    public static function resolverDataProvider(): iterable
    {
        yield 'no allOf elements' => [
            'schema' => new Schema([
                'allOf' => [],
            ]),
            'resolvedTypes' => [],
            'referenceSchemas' => [],
            'expectedAllOfType' => null,
            'expectedExceptionMessage' => '"test" is invalid, reason: no types found',
        ];

        yield 'no types returned' => [
            'schema' => new Schema([
                'allOf' => [
                    [
                        'description' => 'foo bar',
                    ],
                ],
            ]),
            'resolvedTypes' => [null],
            'referenceSchemas' => [],
            'expectedAllOfType' => null,
            'expectedExceptionMessage' => '"test" is invalid, reason: no types found',
        ];

        yield 'oneOf' => [
            'schema' => new Schema([
                'allOf' => [
                    [
                        'oneOf' => [],
                    ],
                ],
            ]),
            'resolvedTypes' => [Types::OneOf],
            'referenceSchemas' => [],
            'expectedAllOfType' => null,
            'expectedExceptionMessage' => '"test" is invalid, reason: oneOf is not allowed, because it can not be resolved to combinable types',
        ];

        yield 'oneOf reference' => [
            'schema' => new Schema([
                'allOf' => [
                    [
                        'oneOf' => [],
                    ],
                ],
            ]),
            'resolvedTypes' => [new OneOfReference(new Schema([]))],
            'referenceSchemas' => [],
            'expectedAllOfType' => null,
            'expectedExceptionMessage' => '"test" is invalid, reason: oneOf is not allowed, because it can not be resolved to combinable types',
        ];

        yield 'null type' => [
            'schema' => new Schema([
                'allOf' => [
                    [
                        'type' => 'null',
                    ],
                    [
                        'description' => 'foo bar',
                    ],
                ],
            ]),
            'resolvedTypes' => [Types::Null, null],
            'referenceSchemas' => [],
            'expectedAllOfType' => new AllOfType(Types::Null, new Schema([
                'type' => 'null',
            ])),
        ];

        yield 'null type with exception' => [
            'schema' => new Schema([
                'allOf' => [
                    [
                        'type' => 'null',
                    ],
                    [
                        'type' => 'string',
                    ],
                ],
            ]),
            'resolvedTypes' => [Types::Null, 'string'],
            'referenceSchemas' => [],
            'expectedAllOfType' => null,
            'expectedExceptionMessage' => '"test" is invalid, reason: additional null type found, use oneOf if you want to set a property nullable',
        ];

        yield 'null type as second element with exception' => [
            'schema' => new Schema([
                'allOf' => [
                    [
                        'type' => 'string',
                    ],
                    [
                        'type' => 'null',
                    ],
                ],
            ]),
            'resolvedTypes' => ['string', Types::Null],
            'referenceSchemas' => [],
            'expectedAllOfType' => null,
            'expectedExceptionMessage' => '"test" is invalid, reason: additional null type found, use oneOf if you want to set a property nullable',
        ];

        yield 'scalar type with exception' => [
            'schema' => new Schema([
                'allOf' => [
                    [
                        'type' => 'string',
                    ],
                    [
                        'type' => 'object',
                    ],
                ],
            ]),
            'resolvedTypes' => ['string', Types::Object],
            'referenceSchemas' => [],
            'expectedAllOfType' => null,
            'expectedExceptionMessage' => '"test" is invalid, reason: found multiple types beside a single type (scalar, enum, date-time), this is not allowed as they can not be combined',
        ];

        yield 'enum type with exception' => [
            'schema' => new Schema([
                'allOf' => [
                    [
                        'type' => 'string',
                        'enum' => ['A', 'B'],
                    ],
                    [
                        'type' => 'object',
                    ],
                ],
            ]),
            'resolvedTypes' => [Types::Enum, Types::Object],
            'referenceSchemas' => [],
            'expectedAllOfType' => null,
            'expectedExceptionMessage' => '"test" is invalid, reason: found multiple types beside a single type (scalar, enum, date-time), this is not allowed as they can not be combined',
        ];

        yield 'array type with exception' => [
            'schema' => new Schema([
                'allOf' => [
                    [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                        ],
                    ],
                    [
                        'type' => 'object',
                    ],
                ],
            ]),
            'resolvedTypes' => [Types::Array, Types::Object],
            'referenceSchemas' => [],
            'expectedAllOfType' => null,
            'expectedExceptionMessage' => '"test" is invalid, reason: found multiple types beside a single type (scalar, enum, date-time), this is not allowed as they can not be combined',
        ];

        yield 'date type with exception' => [
            'schema' => new Schema([
                'allOf' => [
                    [
                        'type' => 'string',
                        'format' => 'date',
                    ],
                    [
                        'type' => 'object',
                    ],
                ],
            ]),
            'resolvedTypes' => [Types::Date, Types::Object],
            'referenceSchemas' => [],
            'expectedAllOfType' => null,
            'expectedExceptionMessage' => '"test" is invalid, reason: found multiple types beside a single type (scalar, enum, date-time), this is not allowed as they can not be combined',
        ];

        yield 'date-time type with exception' => [
            'schema' => new Schema([
                'allOf' => [
                    [
                        'type' => 'string',
                        'format' => 'date-time',
                    ],
                    [
                        'type' => 'object',
                    ],
                ],
            ]),
            'resolvedTypes' => [Types::DateTime, Types::Object],
            'referenceSchemas' => [],
            'expectedAllOfType' => null,
            'expectedExceptionMessage' => '"test" is invalid, reason: found multiple types beside a single type (scalar, enum, date-time), this is not allowed as they can not be combined',
        ];

        yield 'scalar type' => [
            'schema' => new Schema([
                'allOf' => [
                    [
                        'type' => 'string',
                    ],
                    [
                        'description' => 'foo bar',
                    ],
                ],
            ]),
            'resolvedTypes' => ['string', null],
            'referenceSchemas' => [],
            'expectedAllOfType' => new AllOfType('string', new Schema([
                'type' => 'string',
            ])),
        ];

        yield 'enum type' => [
            'schema' => new Schema([
                'allOf' => [
                    [
                        'type' => 'string',
                        'enum' => ['A', 'B'],
                    ],
                    [
                        'description' => 'foo bar',
                    ],
                ],
            ]),
            'resolvedTypes' => [Types::Enum, null],
            'referenceSchemas' => [],
            'expectedAllOfType' => new AllOfType(Types::Enum, new Schema([
                'type' => 'string',
                'enum' => ['A', 'B'],
            ])),
        ];

        yield 'array type' => [
            'schema' => new Schema([
                'allOf' => [
                    [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                        ],
                    ],
                    [
                        'description' => 'foo bar',
                    ],
                ],
            ]),
            'resolvedTypes' => [Types::Array, null],
            'referenceSchemas' => [],
            'expectedAllOfType' => new AllOfType(Types::Array, new Schema([
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                ],
            ])),
        ];

        yield 'date type' => [
            'schema' => new Schema([
                'allOf' => [
                    [
                        'type' => 'string',
                        'format' => 'date',
                    ],
                    [
                        'description' => 'foo bar',
                    ],
                ],
            ]),
            'resolvedTypes' => [Types::Date, null],
            'referenceSchemas' => [],
            'expectedAllOfType' => new AllOfType(Types::Date, new Schema([
                'type' => 'string',
                'format' => 'date',
            ])),
        ];

        yield 'date-time type' => [
            'schema' => new Schema([
                'allOf' => [
                    [
                        'type' => 'string',
                        'format' => 'date-time',
                    ],
                    [
                        'description' => 'foo bar',
                    ],
                ],
            ]),
            'resolvedTypes' => [Types::DateTime, null],
            'referenceSchemas' => [],
            'expectedAllOfType' => new AllOfType(Types::DateTime, new Schema([
                'type' => 'string',
                'format' => 'date-time',
            ])),
        ];

        yield 'scalar reference type' => [
            'schema' => new Schema([
                'allOf' => [
                    new Reference([
                        '$ref' => '#/components/schemas/String',
                    ]),
                    [
                        'description' => 'foo bar',
                    ],
                ],
            ]),
            'resolvedTypes' => [new ScalarType('string', new Schema([
                'type' => 'string',
            ])), null],
            'referenceSchemas' => [],
            'expectedAllOfType' => new AllOfType('string', new Schema([
                'type' => 'string',
            ])),
        ];

        yield 'no reference type resolved' => [
            'schema' => new Schema([
                'allOf' => [
                    new Reference([
                        '$ref' => '#/components/schemas/Test2',
                    ]),
                ],
            ]),
            'resolvedTypes' => [new ClassReference(OpenApiType::Schemas, 'Test2'), null],
            'referenceSchemas' => [
                new SchemaWithName(OpenApiType::Schemas, 'Test2', new Schema([
                    'type' => 'unknown',
                ])),
            ],
            'expectedAllOfType' => null,
            'expectedExceptionMessage' => '"test" is invalid, reason: no types found',
        ];

        yield 'invalid reference type resolved' => [
            'schema' => new Schema([
                'allOf' => [
                    new Reference([
                        '$ref' => '#/components/schemas/Test2',
                    ]),
                ],
            ]),
            'resolvedTypes' => [new ClassReference(OpenApiType::Schemas, 'Test2'), Types::AllOf],
            'referenceSchemas' => [
                new SchemaWithName(OpenApiType::Schemas, 'Test2', new Schema([
                    'allOf' => [],
                ])),
            ],
            'expectedAllOfType' => null,
            'expectedExceptionMessage' => '"test" is invalid, reason: found type "allOf" which is not allowed',
        ];

        yield 'reference type array' => [
            'schema' => new Schema([
                'allOf' => [
                    new Reference([
                        '$ref' => '#/components/schemas/Array',
                    ]),
                ],
            ]),
            'resolvedTypes' => [new ClassReference(OpenApiType::Schemas, 'Array'), Types::Array],
            'referenceSchemas' => [
                new SchemaWithName(OpenApiType::Schemas, 'Test2', new Schema([
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ])),
            ],
            'expectedAllOfType' => new AllOfType(
                Types::Array,
                new Schema([
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ])
            ),
        ];

        yield 'two objects' => [
            'schema' => new Schema([
                'allOf' => [
                    [
                        'type' => 'object',
                        'required' => ['id'],
                        'properties' => [
                            'id' => [
                                'type' => 'number',
                            ],
                            'name' => [
                                'type' => 'string',
                            ],
                        ],
                    ], [
                        'type' => 'object',
                        'required' => ['role'],
                        'properties' => [
                            'role' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ]),
            'resolvedTypes' => [Types::Object, Types::Array],
            'referenceSchemas' => [],
            'expectedAllOfType' => new AllOfType(
                Types::Object,
                new Schema([
                    'type' => 'object',
                    'required' => ['id', 'role'],
                    'properties' => [
                        'id' => [
                            'type' => 'number',
                        ],
                        'name' => [
                            'type' => 'string',
                        ],
                        'role' => [
                            'type' => 'string',
                        ],
                    ],
                ])
            ),
        ];

        yield 'two objects with one reference' => [
            'schema' => new Schema([
                'allOf' => [
                    [
                        'type' => 'object',
                        'required' => ['id'],
                        'properties' => [
                            'id' => [
                                'type' => 'number',
                            ],
                            'name' => [
                                'type' => 'string',
                            ],
                        ],
                    ], new Reference([
                        '$ref' => '#/components/schemas/Test2',
                    ]),
                ],
            ]),
            'resolvedTypes' => [Types::Object, new ClassReference(OpenApiType::Schemas, 'Test2'), Types::Object],
            'referenceSchemas' => [
                new SchemaWithName(OpenApiType::Schemas, 'Test2', new Schema(
                    [
                        'type' => 'object',
                        'required' => ['role'],
                        'properties' => [
                            'role' => [
                                'type' => 'string',
                            ],
                        ],
                    ]
                )),
            ],
            'expectedAllOfType' => new AllOfType(
                Types::Object,
                new Schema([
                    'type' => 'object',
                    'required' => ['id', 'role'],
                    'properties' => [
                        'id' => [
                            'type' => 'number',
                        ],
                        'name' => [
                            'type' => 'string',
                        ],
                        'role' => [
                            'type' => 'string',
                        ],
                    ],
                ])
            ),
        ];

        yield 'two references objects' => [
            'schema' => new Schema([
                'allOf' => [
                    new Reference([
                        '$ref' => '#/components/schemas/Test1',
                    ]),
                    new Reference([
                        '$ref' => '#/components/schemas/Test2',
                    ]),
                ],
            ]),
            'resolvedTypes' => [
                new ClassReference(OpenApiType::Schemas, 'Test1'),
                Types::Object,
                new ClassReference(OpenApiType::Schemas, 'Test2'),
                Types::Object,
            ],
            'referenceSchemas' => [
                new SchemaWithName(OpenApiType::Schemas, 'Test1', new Schema(
                    [
                        'type' => 'object',
                        'required' => ['id'],
                        'properties' => [
                            'id' => [
                                'type' => 'number',
                            ],
                            'name' => [
                                'type' => 'string',
                            ],
                        ],
                    ]
                )),
                new SchemaWithName(OpenApiType::Schemas, 'Test2', new Schema(
                    [
                        'type' => 'object',
                        'required' => ['role'],
                        'properties' => [
                            'role' => [
                                'type' => 'string',
                            ],
                        ],
                    ]
                )),
            ],
            'expectedAllOfType' => new AllOfType(
                Types::Object,
                new Schema([
                    'type' => 'object',
                    'required' => ['id', 'role'],
                    'properties' => [
                        'id' => [
                            'type' => 'number',
                        ],
                        'name' => [
                            'type' => 'string',
                        ],
                        'role' => [
                            'type' => 'string',
                        ],
                    ],
                ])
            ),
        ];
    }

    /**
     * @param array<Types|ClassReference|ScalarType|string|null> $resolvedTypes
     * @param array<SchemaWithName> $referenceSchemas
     */
    #[DataProvider('resolverDataProvider')]
    public function testItResolvesAllOfProperty(
        Schema $schema,
        array $resolvedTypes,
        array $referenceSchemas,
        ?AllOfType $expectedAllOfType,
        ?string $expectedExceptionMessage = null
    ): void {
        if ($expectedExceptionMessage !== null) {
            self::expectException(InvalidAllOfException::class);
            self::expectExceptionMessage($expectedExceptionMessage);
        }

        $typeResolver = $this->createMock(TypeResolver::class);
        $referenceResolver = $this->createMock(ReferenceResolver::class);

        if (count($resolvedTypes) > 0) {
            $typeResolver->expects($this->exactly(count($resolvedTypes)))
                ->method('resolve')
                ->willReturn(...$resolvedTypes);
        } else {
            $typeResolver->expects($this->never())
                ->method('resolve');
        }

        if (count($referenceSchemas) > 0) {
            $referenceResolver->expects($this->exactly(count($referenceSchemas)))
                ->method('resolve')
                ->willReturn(...$referenceSchemas);
        } else {
            $referenceResolver->expects($this->never())
                ->method('resolve');
        }

        $openApi = new OpenApi([]);

        $resolver = new AllOfPropertySchemaResolver($typeResolver, $referenceResolver);

        $type = $resolver->resolve($openApi, $schema, 'test');

        if ($expectedAllOfType !== null) {
            self::assertEquals($expectedAllOfType->type, $type->type);
            self::assertEquals($expectedAllOfType->schema->getSerializableData(), $type->schema->getSerializableData());
        }
    }
}

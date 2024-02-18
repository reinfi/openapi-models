<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Generator;

use Nette\PhpGenerator\Method;
use openapiphp\openapi\spec\Schema;
use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Generator\ClassReference;
use Reinfi\OpenApiModels\Generator\OpenApiType;
use Reinfi\OpenApiModels\Generator\PropertyResolver;
use Reinfi\OpenApiModels\Generator\Types;
use Reinfi\OpenApiModels\Model\OneOfReference;
use Reinfi\OpenApiModels\Model\ScalarType;

class PropertyResolverTest extends TestCase
{
    public static function ParameterDataProvider(): array
    {
        return [
            [
                'name' => 'property',
                'schema' => new Schema([
                    'nullable' => false,
                ]),
                'required' => true,
                'type' => 'string',
                'expectedType' => 'string',
                'nullable' => false,
                'shouldHaveDefaultValueNull' => false,
            ],
            [
                'name' => 'property',
                'schema' => new Schema([
                    'nullable' => true,
                ]),
                'required' => true,
                'type' => 'int',
                'expectedType' => 'int',
                'nullable' => true,
                'shouldHaveDefaultValueNull' => false,
            ],
            [
                'name' => 'property',
                'schema' => new Schema([
                    'nullable' => false,
                ]),
                'required' => false,
                'type' => 'string',
                'expectedType' => 'string',
                'nullable' => true,
                'shouldHaveDefaultValueNull' => true,
            ],
            [
                'name' => 'property',
                'schema' => new Schema([
                    'nullable' => false,
                ]),
                'required' => true,
                'type' => Types::Object,
                'expectedType' => null,
                'nullable' => false,
                'shouldHaveDefaultValueNull' => false,
            ],
            [
                'name' => 'property',
                'schema' => new Schema([
                    'nullable' => false,
                ]),
                'required' => true,
                'type' => Types::Null,
                'expectedType' => 'null',
                'nullable' => false,
                'shouldHaveDefaultValueNull' => false,
            ],
            [
                'name' => 'property',
                'schema' => new Schema([]),
                'required' => false,
                'type' => Types::Array,
                'expectedType' => null,
                'nullable' => true,
                'shouldHaveDefaultValueNull' => true,
            ],
            [
                'name' => 'property',
                'schema' => new Schema([]),
                'required' => false,
                'type' => new ScalarType('int', new Schema([])),
                'expectedType' => 'int',
                'nullable' => true,
                'shouldHaveDefaultValueNull' => true,
            ],
            [
                'name' => 'property',
                'schema' => new Schema([]),
                'required' => true,
                'type' => new ScalarType('int', new Schema([
                    'nullable' => true,
                ])),
                'expectedType' => 'int',
                'nullable' => true,
                'shouldHaveDefaultValueNull' => false,
            ],
            [
                'name' => 'property',
                'schema' => new Schema([]),
                'required' => false,
                'type' => new ClassReference(OpenApiType::Schemas, 'Test4'),
                'expectedType' => 'Test4',
                'nullable' => true,
                'shouldHaveDefaultValueNull' => true,
            ],
            [
                'name' => 'property',
                'schema' => new Schema([]),
                'required' => false,
                'type' => new OneOfReference(new Schema([])),
                'expectedType' => null,
                'nullable' => true,
                'shouldHaveDefaultValueNull' => true,
            ],
        ];
    }

    /**
     * @dataProvider ParameterDataProvider
     */
    public function testItResolvesParameter(
        string $name,
        Schema $schema,
        bool $required,
        ClassReference|OneOfReference|ScalarType|Types|string $type,
        ?string $expectedType,
        bool $nullable,
        bool $shouldHaveDefaultValueNull
    ): void {
        $constructor = new Method('__construct');

        $resolver = new PropertyResolver();

        $parameter = $resolver->resolve($constructor, $name, $schema, $required, $type);

        self::assertEquals($name, $parameter->getName());
        self::assertEquals($expectedType, $parameter->getType());
        self::assertEquals($nullable, $parameter->isNullable());

        if ($shouldHaveDefaultValueNull) {
            self::assertNull($parameter->getDefaultValue());
            self::assertTrue($parameter->isNullable());
        }
    }
}

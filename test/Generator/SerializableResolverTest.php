<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Generator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use DateTimeInterface;
use DG\BypassFinals;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Exception\InvalidDateFormatException;
use Reinfi\OpenApiModels\Exception\PropertyNotFoundException;
use Reinfi\OpenApiModels\Generator\SerializableResolver;
use Reinfi\OpenApiModels\Generator\TypeResolver;
use Reinfi\OpenApiModels\Generator\Types;

class SerializableResolverTest extends TestCase
{
    protected function setUp(): void
    {
        BypassFinals::enable();
    }

    public function testItReturnTrueIfSerializationIsNeeded(): void
    {
        $typeResolver = $this->createMock(TypeResolver::class);

        $resolver = new SerializableResolver($typeResolver);

        $class = new ClassType('Test');
        $class->addMethod('__construct')->addPromotedParameter('date')->setType(DateTimeInterface::class);

        $this->assertTrue($resolver->needsSerialization($class));
    }

    public function testItReturnFalseIfSerializationIsNotNeeded(): void
    {
        $typeResolver = $this->createMock(TypeResolver::class);

        $resolver = new SerializableResolver($typeResolver);

        $class = new ClassType('Test');
        $class->addMethod('__construct')->addPromotedParameter('date')->setType('string');

        $this->assertFalse($resolver->needsSerialization($class));
    }

    public function testItDoesNothingIfNotParameterIsFound(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('Api');
        $schema = new Schema([]);
        $configuration = new Configuration([], '', '');

        $typeResolver = $this->createMock(TypeResolver::class);
        $typeResolver->expects($this->never())->method('resolve');

        $resolver = new SerializableResolver($typeResolver);

        $class = new ClassType('Test');
        $constructor = $class->addMethod('__construct');

        $resolver->addSerialization($configuration, $openApi, $schema, $namespace, $class, $constructor);

        self::assertCount(0, $namespace->getUses());
    }

    public function testItAddsMethodAndBody(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('Api');
        $configuration = new Configuration([], '', '');

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

        $typeResolver = $this->createMock(TypeResolver::class);
        $typeResolver->expects($this->exactly(2))->method('resolve')->willReturn(Types::Date, Types::DateTime);

        $resolver = new SerializableResolver($typeResolver);

        $class = new ClassType('Test');
        $constructor = $class->addMethod('__construct');
        $constructor->addPromotedParameter('date')->setType(DateTimeInterface::class);
        $constructor->addPromotedParameter('dateTime')->setType(DateTimeInterface::class)->setNullable();

        $resolver->addSerialization($configuration, $openApi, $schema, $namespace, $class, $constructor);

        self::assertCount(1, $namespace->getUses());
        self::assertCount(2, $class->getMethods());
        self::assertCount(1, $class->getImplements());

        $method = $class->getMethod('jsonSerialize');

        self::assertEquals('array', $method->getReturnType());
        self::assertStringContainsString('\'date\' => $this->date->format(\'Y-m-d\'),', $method->getBody());
        self::assertStringContainsString(
            '\'dateTime\' => $this->dateTime?->format(\'Y-m-d\TH:i:sP\'),',
            $method->getBody()
        );
    }

    public function testItUsesDateTimeFormatFromConfiguration(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('Api');
        $configuration = new Configuration([], '', '', dateFormat: 'd.m.Y', dateTimeFormat: 'd.m.Y H:i:s');

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

        $typeResolver = $this->createMock(TypeResolver::class);
        $typeResolver->expects($this->exactly(2))->method('resolve')->willReturn(Types::Date, Types::DateTime);

        $resolver = new SerializableResolver($typeResolver);

        $class = new ClassType('Test');
        $constructor = $class->addMethod('__construct');
        $constructor->addPromotedParameter('date')->setType(DateTimeInterface::class);
        $constructor->addPromotedParameter('dateTime')->setType(DateTimeInterface::class)->setNullable();

        $resolver->addSerialization($configuration, $openApi, $schema, $namespace, $class, $constructor);

        self::assertCount(1, $namespace->getUses());
        self::assertCount(2, $class->getMethods());
        self::assertCount(1, $class->getImplements());

        $method = $class->getMethod('jsonSerialize');

        self::assertEquals('array', $method->getReturnType());
        self::assertStringContainsString('\'date\' => $this->date->format(\'d.m.Y\'),', $method->getBody());
        self::assertStringContainsString(
            '\'dateTime\' => $this->dateTime?->format(\'d.m.Y H:i:s\'),',
            $method->getBody()
        );
    }

    public function testItWorksForDateTimeInOneOf(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('Api');
        $configuration = new Configuration([], '', '');

        $schema = new Schema([
            'properties' => [
                'date' => [
                    'oneOf' => [
                        [
                            'type' => 'number',
                        ],
                        [
                            'type' => 'string',
                            'format' => 'date',
                        ],
                    ],
                ],
            ],
        ]);

        $typeResolver = $this->createMock(TypeResolver::class);
        $typeResolver->expects($this->exactly(3))->method('resolve')->willReturn(Types::OneOf, 'int', Types::Date);

        $resolver = new SerializableResolver($typeResolver);

        $class = new ClassType('Test');
        $constructor = $class->addMethod('__construct');
        $constructor->addPromotedParameter('date')->setType(sprintf('int|%s', DateTimeInterface::class));

        $resolver->addSerialization($configuration, $openApi, $schema, $namespace, $class, $constructor);

        self::assertCount(1, $namespace->getUses());
        self::assertCount(2, $class->getMethods());
        self::assertCount(1, $class->getImplements());

        $method = $class->getMethod('jsonSerialize');

        self::assertEquals('array', $method->getReturnType());
        self::assertStringContainsString(
            '\'date\' => $this->date instanceOf DateTimeInterface ? $this->date->format(\'Y-m-d\') : $this->date',
            $method->getBody()
        );
    }

    public function testItWorksForDateTimeInArray(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('Api');
        $configuration = new Configuration([], '', '');

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

        $typeResolver = $this->createMock(TypeResolver::class);
        $typeResolver->expects($this->exactly(2))->method('resolve')->willReturn(Types::Array, Types::Date);

        $resolver = new SerializableResolver($typeResolver);

        $class = new ClassType('Test');
        $constructor = $class->addMethod('__construct');
        $constructor->addPromotedParameter('dates')->setType('array')->setComment(
            '@var array<DateTimeInterface> $dates'
        );

        $resolver->addSerialization($configuration, $openApi, $schema, $namespace, $class, $constructor);

        self::assertCount(1, $namespace->getUses());
        self::assertCount(2, $class->getMethods());
        self::assertCount(1, $class->getImplements());

        $method = $class->getMethod('jsonSerialize');

        self::assertEquals('array', $method->getReturnType());
        self::assertStringContainsString(
            '\'dates\' => array_map(static fn (DateTimeInterface $date): string => $date->format(\'Y-m-d\'), $this->dates)',
            $method->getBody()
        );
    }

    public function testItWorksForDateTimeInArrayWithNullableParameter(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('Api');
        $configuration = new Configuration([], '', '');

        $schema = new Schema([
            'properties' => [
                'dates' => [
                    'type' => 'array',
                    'nullable' => true,
                    'items' => [
                        'type' => 'string',
                        'format' => 'date-time',
                    ],
                ],
            ],
        ]);

        $typeResolver = $this->createMock(TypeResolver::class);
        $typeResolver->expects($this->exactly(2))->method('resolve')->willReturn(Types::Array, Types::DateTime);

        $resolver = new SerializableResolver($typeResolver);

        $class = new ClassType('Test');
        $constructor = $class->addMethod('__construct');
        $constructor->addPromotedParameter('dates')->setNullable()->setType('array')->setComment(
            '@var array<DateTimeInterface> $dates'
        );

        $resolver->addSerialization($configuration, $openApi, $schema, $namespace, $class, $constructor);

        self::assertCount(1, $namespace->getUses());
        self::assertCount(2, $class->getMethods());
        self::assertCount(1, $class->getImplements());

        $method = $class->getMethod('jsonSerialize');

        self::assertEquals('array', $method->getReturnType());
        self::assertStringContainsString(
            '\'dates\' => $this->dates === null ? $this->dates : array_map(static fn (DateTimeInterface $date): string => $date->format(\'Y-m-d\TH:i:sP\'), $this->dates)',
            $method->getBody()
        );
    }

    public function testItThrowsExceptionIfPropertyNotFoundInSchema(): void
    {
        self::expectException(PropertyNotFoundException::class);
        self::expectExceptionMessage('Property "date" was not found in schema');

        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');
        $configuration = new Configuration([], '', '');

        $schema = new Schema([]);

        $typeResolver = $this->createMock(TypeResolver::class);
        $typeResolver->expects($this->never())->method('resolve');

        $resolver = new SerializableResolver($typeResolver);

        $class = new ClassType('Test');
        $constructor = $class->addMethod('__construct');
        $constructor->addPromotedParameter('date')->setType(DateTimeInterface::class);

        $resolver->addSerialization($configuration, $openApi, $schema, $namespace, $class, $constructor);
    }

    public function testItThrowsExceptionIfPropertyHasInvalidType(): void
    {
        self::expectException(InvalidDateFormatException::class);
        self::expectExceptionMessage('Invalid date format found for property "date"');

        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');
        $configuration = new Configuration([], '', '');

        $schema = new Schema([
            'properties' => [
                'date' => [
                    'type' => 'string',
                    'format' => 'time',
                ],
            ],
        ]);

        $typeResolver = $this->createMock(TypeResolver::class);
        $typeResolver->expects($this->once())->method('resolve')->willReturn(Types::Object);

        $resolver = new SerializableResolver($typeResolver);

        $class = new ClassType('Test');
        $constructor = $class->addMethod('__construct');
        $constructor->addPromotedParameter('date')->setType(DateTimeInterface::class);

        $resolver->addSerialization($configuration, $openApi, $schema, $namespace, $class, $constructor);
    }
}

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

        $typeResolver = $this->createMock(TypeResolver::class);
        $typeResolver->expects($this->never())->method('resolve');

        $resolver = new SerializableResolver($typeResolver);

        $class = new ClassType('Test');
        $constructor = $class->addMethod('__construct');

        $resolver->addSerialization($openApi, $schema, $namespace, $class, $constructor);

        self::assertCount(0, $namespace->getUses());
    }

    public function testItAddsMethodAndBody(): void
    {
        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('Api');

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

        $resolver->addSerialization($openApi, $schema, $namespace, $class, $constructor);

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

    public function testItThrowsExceptionIfPropertyNotFoundInSchema(): void
    {
        self::expectException(PropertyNotFoundException::class);
        self::expectExceptionMessage('Property "date" was not found in schema');

        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');

        $schema = new Schema([]);

        $typeResolver = $this->createMock(TypeResolver::class);
        $typeResolver->expects($this->never())->method('resolve');

        $resolver = new SerializableResolver($typeResolver);

        $class = new ClassType('Test');
        $constructor = $class->addMethod('__construct');
        $constructor->addPromotedParameter('date')->setType(DateTimeInterface::class);

        $resolver->addSerialization($openApi, $schema, $namespace, $class, $constructor);
    }

    public function testItThrowsExceptionIfPropertyHasInvalidType(): void
    {
        self::expectException(InvalidDateFormatException::class);
        self::expectExceptionMessage('Invalid date format found for property "date"');

        $openApi = new OpenApi([]);
        $namespace = new PhpNamespace('');

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

        $resolver->addSerialization($openApi, $schema, $namespace, $class, $constructor);
    }
}

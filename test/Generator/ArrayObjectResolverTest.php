<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Generator;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use Countable;
use IteratorAggregate;
use Nette\PhpGenerator\ClassLike;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Generator\ArrayObjectResolver;
use Reinfi\OpenApiModels\Generator\ClassReference;
use Reinfi\OpenApiModels\Generator\OpenApiType;
use Reinfi\OpenApiModels\Model\ArrayType;
use Reinfi\OpenApiModels\Model\Imports;
use Traversable;

class ArrayObjectResolverTest extends TestCase
{
    /**
     * @return iterable<array{nullable: bool}>
     */
    public static function nullableDataProvider(): iterable
    {
        yield [
            'nullable' => true,
        ];

        yield [
            'nullable' => false,
        ];
    }

    /**
     * @return iterable<array{arrayType: string|ClassReference, expectedDocComments: string[]}>
     */
    public static function typingsInterfaceDataProvider(): iterable
    {
        yield [
            'arrayType' => 'string',
            'expectedDocComments' => [
                '@implements ArrayAccess<int, string>',
                '@implements IteratorAggregate<string>',
            ],
        ];

        yield [
            'arrayType' => 'Api\Schema\Test1',
            'expectedDocComments' => [
                '@implements ArrayAccess<int, Test1>',
                '@implements IteratorAggregate<Test1>',
            ],
        ];

        yield [
            'arrayType' => 'DateTimeInterface',
            'expectedDocComments' => [
                '@implements ArrayAccess<int, DateTimeInterface>',
                '@implements IteratorAggregate<DateTimeInterface>',
            ],
        ];

        yield [
            'arrayType' => 'Api\Schema\Test1|Api\Schema\Test2',
            'expectedDocComments' => [
                '@implements ArrayAccess<int, Test1|Test2>',
                '@implements IteratorAggregate<Test1|Test2>',
            ],
        ];
    }

    public function testItResolvesScalarNotNullableTypeToParameter(): void
    {
        $namespace = new PhpNamespace('');
        $class = new ClassType();
        $constructor = new Method('__construct');
        $imports = new Imports(new PhpNamespace(''));

        $arrayType = new ArrayType('string', nullable: false, docType: 'docType');

        $resolver = new ArrayObjectResolver();

        $resolver->resolve($class, $constructor, $arrayType, $imports, $namespace);

        self::assertTrue($constructor->isVariadic());
        self::assertCount(1, $constructor->getParameters());
        self::assertTrue($constructor->hasParameter('items'));
        self::assertStringContainsString('$this->items = $items;', $constructor->getBody());

        $parameter = $constructor->getParameter('items');
        self::assertEquals('string', $parameter->getType());
        self::assertFalse($parameter->isNullable());

        self::assertCount(1, $class->getProperties());
        self::assertTrue($class->hasProperty('items'));

        $property = $class->getProperty('items');
        self::assertEquals('array', $property->getType());
        self::assertFalse($property->isNullable());
        self::assertEquals(ClassLike::VisibilityPrivate, $property->getVisibility());
        self::assertStringContainsString('docType', $property->getComment() ?: '');
    }

    public function testItResolvesScalarNullableTypeToParameter(): void
    {
        $namespace = new PhpNamespace('');
        $class = new ClassType();
        $constructor = new Method('__construct');
        $imports = new Imports(new PhpNamespace(''));

        $arrayType = new ArrayType('string', nullable: true, docType: 'docType');

        $resolver = new ArrayObjectResolver();

        $resolver->resolve($class, $constructor, $arrayType, $imports, $namespace);

        self::assertFalse($constructor->isVariadic());
        self::assertCount(1, $constructor->getParameters());
        self::assertTrue($constructor->hasParameter('items'));
        self::assertEmpty($constructor->getBody());

        $parameter = $constructor->getParameter('items');
        self::assertEquals('array', $parameter->getType());
        self::assertTrue($parameter->isNullable());
        self::assertStringContainsString('docType', $parameter->getComment() ?: '');

        self::assertCount(0, $class->getProperties());
        self::assertFalse($class->hasProperty('items'));
    }

    public function testItResolvesReferenceToParameter(): void
    {
        $class = new ClassType();
        $constructor = new Method('__construct');
        $namespace = new PhpNamespace('Api');
        $imports = new Imports($namespace);

        $arrayType = new ArrayType(new ClassReference(
            OpenApiType::Schemas,
            'Test1'
        ), nullable: false, docType: 'docType');

        $resolver = new ArrayObjectResolver();

        $resolver->resolve($class, $constructor, $arrayType, $imports, $namespace);

        self::assertTrue($constructor->isVariadic());
        self::assertCount(1, $constructor->getParameters());
        self::assertTrue($constructor->hasParameter('items'));
        self::assertStringContainsString('$this->items = $items;', $constructor->getBody());

        $parameter = $constructor->getParameter('items');
        self::assertEquals('Test1', $parameter->getType());
        self::assertFalse($parameter->isNullable());

        self::assertCount(1, $class->getProperties());
        self::assertTrue($class->hasProperty('items'));

        $property = $class->getProperty('items');
        self::assertEquals('array', $property->getType());
        self::assertFalse($property->isNullable());
        self::assertEquals(ClassLike::VisibilityPrivate, $property->getVisibility());
        self::assertStringContainsString('docType', $property->getComment() ?: '');

        $imports->copyImports();

        self::assertContains('Test1', $namespace->getUses());
    }

    public function testItImplementsExpectedInterfaces(): void
    {
        $namespace = new PhpNamespace('');
        $class = new ClassType();
        $constructor = new Method('__construct');
        $imports = $this->createMock(Imports::class);

        $arrayType = new ArrayType('string', nullable: false, docType: 'docType');

        $resolver = new ArrayObjectResolver();

        $resolver->resolve($class, $constructor, $arrayType, $imports, $namespace);

        self::assertEquals([IteratorAggregate::class, Countable::class, ArrayAccess::class], $class->getImplements());
    }

    /**
     * @dataProvider typingsInterfaceDataProvider
     *
     * @param array<string>                 $expectedDocComments
     */
    public function testItAddsTypingsForInterfaces(string|ClassReference $arrayType, array $expectedDocComments): void
    {
        $namespace = new PhpNamespace('Api\Schema');
        $class = new ClassType();
        $constructor = new Method('__construct');
        $imports = $this->createMock(Imports::class);

        $arrayType = new ArrayType($arrayType, nullable: false, docType: 'docType');

        $resolver = new ArrayObjectResolver();

        $resolver->resolve($class, $constructor, $arrayType, $imports, $namespace);

        self::assertNotNull($class->getComment());

        foreach ($expectedDocComments as $docComment) {
            self::assertStringContainsString($docComment, $class->getComment());
        }
    }

    /**
     * @dataProvider nullableDataProvider
     */
    public function testItAddsIteratorMethod(bool $nullable): void
    {
        $class = new ClassType();
        $constructor = new Method('__construct');
        $namespace = new PhpNamespace('Api');
        $imports = new Imports($namespace);

        $arrayType = new ArrayType('string', nullable: $nullable, docType: 'docType');

        $resolver = new ArrayObjectResolver();

        $resolver->resolve($class, $constructor, $arrayType, $imports, $namespace);

        self::assertTrue($class->hasMethod('getIterator'));

        $method = $class->getMethod('getIterator');
        self::assertEquals(Traversable::class, $method->getReturnType());

        if ($nullable) {
            self::assertStringContainsString('return new ArrayIterator($this->items ?? []);', $method->getBody());
        } else {
            self::assertStringContainsString('return new ArrayIterator($this->items);', $method->getBody());
        }

        $imports->copyImports();

        self::assertContains(Traversable::class, $namespace->getUses());
        self::assertContains(ArrayIterator::class, $namespace->getUses());
    }

    /**
     * @dataProvider nullableDataProvider
     */
    public function testItAddsCountMethod(bool $nullable): void
    {
        $class = new ClassType();
        $constructor = new Method('__construct');
        $namespace = new PhpNamespace('Api');
        $imports = new Imports($namespace);

        $arrayType = new ArrayType('string', nullable: $nullable, docType: 'docType');

        $resolver = new ArrayObjectResolver();

        $resolver->resolve($class, $constructor, $arrayType, $imports, $namespace);

        self::assertTrue($class->hasMethod('count'));

        $method = $class->getMethod('count');
        self::assertEquals('int', $method->getReturnType());

        if ($nullable) {
            self::assertStringContainsString('return count($this->items ?? []);', $method->getBody());
        } else {
            self::assertStringContainsString('return count($this->items);', $method->getBody());
        }
    }

    public function testItAddsOffsetExistsMethod(): void
    {
        $class = new ClassType();
        $constructor = new Method('__construct');
        $namespace = new PhpNamespace('Api');
        $imports = new Imports($namespace);

        $arrayType = new ArrayType('string', nullable: false, docType: 'docType');

        $resolver = new ArrayObjectResolver();

        $resolver->resolve($class, $constructor, $arrayType, $imports, $namespace);

        self::assertTrue($class->hasMethod('offsetExists'));

        $method = $class->getMethod('offsetExists');
        self::assertEquals('bool', $method->getReturnType());
        self::assertTrue($method->hasParameter('offset'));
        self::assertEquals('mixed', $method->getParameter('offset')->getType());

        self::assertStringContainsString('return isset($this->items[$offset]);', $method->getBody());
    }

    public function testItAddsOffsetGetMethod(): void
    {
        $namespace = new PhpNamespace('');
        $class = new ClassType();
        $constructor = new Method('__construct');
        $namespace = new PhpNamespace('Api');
        $imports = new Imports($namespace);

        $arrayType = new ArrayType('string', nullable: false, docType: 'docType');

        $resolver = new ArrayObjectResolver();

        $resolver->resolve($class, $constructor, $arrayType, $imports, $namespace);

        self::assertTrue($class->hasMethod('offsetGet'));

        $method = $class->getMethod('offsetGet');
        self::assertEquals('string', $method->getReturnType());
        self::assertTrue($method->isReturnNullable());
        self::assertTrue($method->hasParameter('offset'));
        self::assertEquals('mixed', $method->getParameter('offset')->getType());

        self::assertStringContainsString('return $this->items[$offset] ?? null;', $method->getBody());
    }

    public function testItAddsOffsetGetMethodForReference(): void
    {
        $namespace = new PhpNamespace('');
        $class = new ClassType();
        $constructor = new Method('__construct');
        $namespace = new PhpNamespace('Api');
        $imports = new Imports($namespace);

        $arrayType = new ArrayType(new ClassReference(
            OpenApiType::Schemas,
            'Test1'
        ), nullable: false, docType: 'docType');

        $resolver = new ArrayObjectResolver();

        $resolver->resolve($class, $constructor, $arrayType, $imports, $namespace);

        self::assertTrue($class->hasMethod('offsetGet'));

        $method = $class->getMethod('offsetGet');
        self::assertEquals('Test1', $method->getReturnType());
        self::assertTrue($method->isReturnNullable());
        self::assertTrue($method->hasParameter('offset'));
        self::assertEquals('mixed', $method->getParameter('offset')->getType());

        self::assertStringContainsString('return $this->items[$offset] ?? null;', $method->getBody());
    }

    public function testItAddsOffsetSetMethod(): void
    {
        $class = new ClassType();
        $constructor = new Method('__construct');
        $namespace = new PhpNamespace('Api');
        $imports = new Imports($namespace);

        $arrayType = new ArrayType('string', nullable: false, docType: 'docType');

        $resolver = new ArrayObjectResolver();

        $resolver->resolve($class, $constructor, $arrayType, $imports, $namespace);

        self::assertTrue($class->hasMethod('offsetSet'));

        $method = $class->getMethod('offsetSet');
        self::assertEquals('void', $method->getReturnType());
        self::assertTrue($method->hasParameter('offset'));
        self::assertEquals('mixed', $method->getParameter('offset')->getType());
        self::assertTrue($method->hasParameter('value'));
        self::assertEquals('mixed', $method->getParameter('value')->getType());

        self::assertStringContainsString(
            'throw new BadMethodCallException(\'Object is readOnly\');',
            $method->getBody()
        );

        $imports->copyImports();

        self::assertContains(BadMethodCallException::class, $namespace->getUses());
    }

    public function testItAddsOffsetUnsetMethod(): void
    {
        $class = new ClassType();
        $constructor = new Method('__construct');
        $namespace = new PhpNamespace('Api');
        $imports = new Imports($namespace);

        $arrayType = new ArrayType('string', nullable: false, docType: 'docType');

        $resolver = new ArrayObjectResolver();

        $resolver->resolve($class, $constructor, $arrayType, $imports, $namespace);

        self::assertTrue($class->hasMethod('offsetUnset'));

        $method = $class->getMethod('offsetUnset');
        self::assertEquals('void', $method->getReturnType());
        self::assertTrue($method->hasParameter('offset'));
        self::assertEquals('mixed', $method->getParameter('offset')->getType());

        self::assertStringContainsString(
            'throw new BadMethodCallException(\'Object is readOnly\');',
            $method->getBody()
        );
    }
}

<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Generator;

use Nette\PhpGenerator\PhpNamespace;
use openapiphp\openapi\spec\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Exception\NotRegisteredNamespaceException;
use Reinfi\OpenApiModels\Generator\NamespaceResolver;
use Reinfi\OpenApiModels\Generator\OpenApiType;

class NamespaceResolverTest extends TestCase
{
    public static function resolveNamespaceDataProvider(): iterable
    {
        yield [
            'openApiType' => OpenApiType::Schemas,
            'configurationNamespace' => '',
            'expectedNamespace' => 'Schema',
        ];
        yield [
            'openApiType' => OpenApiType::RequestBodies,
            'configurationNamespace' => '',
            'expectedNamespace' => 'RequestBody',
        ];
        yield [
            'openApiType' => OpenApiType::Responses,
            'configurationNamespace' => '',
            'expectedNamespace' => 'Response',
        ];
        yield [
            'openApiType' => OpenApiType::Schemas,
            'configurationNamespace' => 'Api',
            'expectedNamespace' => 'Api\Schema',
        ];
        yield [
            'openApiType' => OpenApiType::RequestBodies,
            'configurationNamespace' => 'Api',
            'expectedNamespace' => 'Api\RequestBody',
        ];
        yield [
            'openApiType' => OpenApiType::Responses,
            'configurationNamespace' => 'Api',
            'expectedNamespace' => 'Api\Response',
        ];
    }

    public function testItInitializesNamespacesFromConfiguration(): void
    {
        $configuration = new Configuration([], '', '');

        $resolver = new NamespaceResolver();

        $resolver->initialize($configuration);

        $namespacesProperty = (new ReflectionClass($resolver))->getProperty('openApiTypeToNamespace');
        $namespacesProperty->setAccessible(true);

        $namespaces = $namespacesProperty->getValue($resolver);

        self::assertIsIterable($namespaces);
        self::assertIsArray($namespaces);
        self::assertCount(3, $namespaces);
        self::assertContainsOnlyInstancesOf(PhpNamespace::class, $namespaces);
        self::assertArrayHasKey(OpenApiType::Schemas->value, $namespaces);
        self::assertArrayHasKey(OpenApiType::RequestBodies->value, $namespaces);
        self::assertArrayHasKey(OpenApiType::Responses->value, $namespaces);
    }

    #[DataProvider('resolveNamespaceDataProvider')]
    public function testItResolvesNamespace(
        OpenApiType $openApiType,
        string $configurationNamespace,
        string $expectedNamespace
    ): void {
        $configuration = new Configuration([], '', $configurationNamespace);

        $resolver = new NamespaceResolver();
        $resolver->initialize($configuration);

        $schema = new Schema([
            'type' => 'object',
        ]);
        $namespace = $resolver->resolveNamespace($openApiType, $schema);

        self::assertEquals($expectedNamespace, $namespace->getName());
    }

    public function testItResolvesNamespaceWithXPhpNamespace(): void
    {
        $configuration = new Configuration([], '', 'Api');
        $resolver = new NamespaceResolver();
        $resolver->initialize($configuration);

        $schema = new Schema([
            'type' => 'object',
            'x-php-namespace' => 'Custom',
        ]);

        $namespace = $resolver->resolveNamespace(OpenApiType::Schemas, $schema);

        self::assertEquals('Api\Schema\Custom', $namespace->getName());
    }

    public function testItThrowsExceptionIfNamespaceNotKnown(): void
    {
        $this->expectException(NotRegisteredNamespaceException::class);
        $this->expectExceptionMessage('No namespace is registered for open api type schemas');

        $resolver = new NamespaceResolver();
        $schema = new Schema([
            'type' => 'object',
        ]);
        $resolver->resolveNamespace(OpenApiType::Schemas, $schema);
    }
}

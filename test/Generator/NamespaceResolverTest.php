<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Generator;

use Nette\PhpGenerator\PhpNamespace;
use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Exception\NotRegisteredNamespaceException;
use Reinfi\OpenApiModels\Generator\NamespaceResolver;
use Reinfi\OpenApiModels\Generator\OpenApiType;

class NamespaceResolverTest extends TestCase
{
    public static function ResolveNamespaceDataProvider(): iterable
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
        $configuration = new Configuration([], '', '', false);

        $resolver = new NamespaceResolver();

        $resolver->initialize($configuration);

        $namespaces = $resolver->getNamespaces();

        self::assertCount(3, $namespaces);
        self::assertContainsOnlyInstancesOf(PhpNamespace::class, $namespaces);
        self::assertArrayHasKey(OpenApiType::Schemas->value, $namespaces);
        self::assertArrayHasKey(OpenApiType::RequestBodies->value, $namespaces);
        self::assertArrayHasKey(OpenApiType::Responses->value, $namespaces);
    }

    /**
     * @dataProvider ResolveNamespaceDataProvider
     */
    public function testItResolvesNamespace(
        OpenApiType $openApiType,
        string $configurationNamespace,
        string $expectedNameSpace
    ): void {
        $configuration = new Configuration([], '', $configurationNamespace, false);

        $resolver = new NamespaceResolver();

        $resolver->initialize($configuration);

        $namespace = $resolver->resolveNamespace($openApiType);

        self::assertEquals($expectedNameSpace, $namespace->getName());
    }

    public function testItThrowsExceptionIfNamespaceNotKnown(): void
    {
        self::expectException(NotRegisteredNamespaceException::class);
        self::expectExceptionMessage('No namespace is registered for open api type schemas');

        $resolver = new NamespaceResolver();

        $resolver->resolveNamespace(OpenApiType::Schemas);
    }
}

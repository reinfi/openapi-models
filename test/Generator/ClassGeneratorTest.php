<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Generator;

use DG\BypassFinals;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use openapiphp\openapi\spec\OpenApi;
use openapiphp\openapi\spec\Reference;
use openapiphp\openapi\spec\Schema;
use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Exception\OnlyJsonContentTypeSupported;
use Reinfi\OpenApiModels\Generator\ClassGenerator;
use Reinfi\OpenApiModels\Generator\ClassTransformer;
use Reinfi\OpenApiModels\Generator\NamespaceResolver;
use Reinfi\OpenApiModels\Generator\OpenApiType;

class ClassGeneratorTest extends TestCase
{
    protected function setUp(): void
    {
        BypassFinals::enable();
    }

    public function testItGeneratesClassesFromOpenApi(): void
    {
        $configuration = new Configuration([], '', '');
        $namespace = new PhpNamespace('Schema');

        $openApi = new OpenApi([
            'components' => [
                'schemas' => [
                    'Test1' => [
                        'type' => 'object',
                    ],
                    'Test2' => [
                        'type' => 'object',
                    ],
                    'Test3' => [
                        '$ref' => '#/components/schemas/Test4',
                    ],
                ],
            ],
        ]);

        $transformer = $this->createMock(ClassTransformer::class);
        $transformer->expects($this->exactly(2))
            ->method('transform')
            ->with(
                $configuration,
                $openApi,
                $this->callback(static fn (string $name): bool => in_array($name, ['Test1', 'Test2'], true)),
                $this->isInstanceOf(Schema::class),
                $namespace
            )->willReturn(new ClassType());

        $namespaceResolver = $this->createMock(NamespaceResolver::class);
        $namespaceResolver->expects($this->once())
            ->method('initialize')
            ->with($configuration);
        $namespaceResolver->expects($this->once())
            ->method('resolveNamespace')
            ->with(OpenApiType::Schemas)->willReturn($namespace);

        $generator = new ClassGenerator($transformer, $namespaceResolver);

        $generator->generate($openApi, $configuration);
    }

    public function testItGeneratesRequestBodies(): void
    {
        $configuration = new Configuration([], '', '');
        $namespace = new PhpNamespace('RequestBody');

        $openApi = new OpenApi([
            'components' => [
                'requestBodies' => [
                    'Test1' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $transformer = $this->createMock(ClassTransformer::class);
        $transformer->expects($this->once())
            ->method('transform')
            ->with(
                $configuration,
                $openApi,
                'Test1',
                $this->isInstanceOf(Schema::class),
                $namespace
            )->willReturn(new ClassType());

        $namespaceResolver = $this->createMock(NamespaceResolver::class);
        $namespaceResolver->expects($this->once())
            ->method('initialize')
            ->with($configuration);
        $namespaceResolver->expects($this->once())
            ->method('resolveNamespace')
            ->with(OpenApiType::RequestBodies)->willReturn($namespace);

        $generator = new ClassGenerator($transformer, $namespaceResolver);

        $generator->generate($openApi, $configuration);
    }

    public function testItGeneratesResponses(): void
    {
        $configuration = new Configuration([], '', '');
        $namespace = new PhpNamespace('Response');

        $openApi = new OpenApi([
            'components' => [
                'responses' => [
                    'Test1' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $transformer = $this->createMock(ClassTransformer::class);
        $transformer->expects($this->once())
            ->method('transform')
            ->with(
                $configuration,
                $openApi,
                'Test1',
                $this->isInstanceOf(Schema::class),
                $namespace
            )->willReturn(new ClassType());

        $namespaceResolver = $this->createMock(NamespaceResolver::class);
        $namespaceResolver->expects($this->once())
            ->method('initialize')
            ->with($configuration);
        $namespaceResolver->expects($this->once())
            ->method('resolveNamespace')
            ->with(OpenApiType::Responses)->willReturn($namespace);

        $generator = new ClassGenerator($transformer, $namespaceResolver);

        $generator->generate($openApi, $configuration);
    }

    public function testItGeneratesReferenceClasses(): void
    {
        $configuration = new Configuration([], '', '');
        $namespace = new PhpNamespace('Response');

        $openApi = new OpenApi([
            'components' => [
                'responses' => [
                    'Test1' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/Test2',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $transformer = $this->createMock(ClassTransformer::class);
        $transformer->expects($this->once())
            ->method('transform')
            ->with(
                $configuration,
                $openApi,
                'Test1',
                $this->isInstanceOf(Reference::class),
                $namespace
            )->willReturn(new ClassType());

        $namespaceResolver = $this->createMock(NamespaceResolver::class);
        $namespaceResolver->expects($this->once())
            ->method('initialize')
            ->with($configuration);
        $namespaceResolver->expects($this->once())
            ->method('resolveNamespace')
            ->with(OpenApiType::Responses)->willReturn($namespace);

        $generator = new ClassGenerator($transformer, $namespaceResolver);

        $generator->generate($openApi, $configuration);
    }

    public function testItGeneratesOnlyJsonSchema(): void
    {
        $configuration = new Configuration([], '', '');
        $namespace = new PhpNamespace('Response');

        $openApi = new OpenApi([
            'components' => [
                'responses' => [
                    'Test1' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                ],
                            ],
                            'application/xml' => [
                                'schema' => [
                                    'type' => 'object',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $transformer = $this->createMock(ClassTransformer::class);
        $transformer->expects($this->once())
            ->method('transform')
            ->with(
                $configuration,
                $openApi,
                'Test1',
                $this->isInstanceOf(Schema::class),
                $namespace
            )->willReturn(new ClassType());

        $namespaceResolver = $this->createMock(NamespaceResolver::class);
        $namespaceResolver->expects($this->once())
            ->method('initialize')
            ->with($configuration);
        $namespaceResolver->expects($this->once())
            ->method('resolveNamespace')
            ->with(OpenApiType::Responses)->willReturn($namespace);

        $generator = new ClassGenerator($transformer, $namespaceResolver);

        $generator->generate($openApi, $configuration);
    }

    public function testItThrowsExceptionIfJsonContentTypeNotFound(): void
    {
        self::expectException(OnlyJsonContentTypeSupported::class);
        self::expectExceptionMessage('Only "application/json" is supported as media type, found: application/xml');

        $configuration = new Configuration([], '', '');
        $namespace = new PhpNamespace('Response');

        $openApi = new OpenApi([
            'components' => [
                'responses' => [
                    'Test1' => [
                        'content' => [
                            'application/xml' => [
                                'schema' => [
                                    'type' => 'object',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $transformer = $this->createMock(ClassTransformer::class);
        $transformer->expects($this->never())
            ->method('transform');

        $namespaceResolver = $this->createMock(NamespaceResolver::class);
        $namespaceResolver->expects($this->once())
            ->method('initialize')
            ->with($configuration);
        $namespaceResolver->expects($this->once())
            ->method('resolveNamespace')
            ->with(OpenApiType::Responses)->willReturn($namespace);

        $generator = new ClassGenerator($transformer, $namespaceResolver);

        $generator->generate($openApi, $configuration);
    }

    public function testItSetsCommentIfTopLevelHasDescription(): void
    {
        $configuration = new Configuration([], '', '');
        $namespace = new PhpNamespace('Response');

        $openApi = new OpenApi([
            'components' => [
                'responses' => [
                    'Test1' => [
                        'description' => 'test',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $transformer = $this->createMock(ClassTransformer::class);
        $transformer->expects($this->once())
            ->method('transform')
            ->with(
                $configuration,
                $openApi,
                'Test1',
                $this->isInstanceOf(Schema::class),
                $namespace
            )->willReturn(new ClassType());

        $namespaceResolver = $this->createMock(NamespaceResolver::class);
        $namespaceResolver->expects($this->once())
            ->method('initialize')
            ->with($configuration);
        $namespaceResolver->expects($this->once())
            ->method('resolveNamespace')
            ->with(OpenApiType::Responses)->willReturn($namespace);

        $generator = new ClassGenerator($transformer, $namespaceResolver);

        $generator->generate($openApi, $configuration);
    }
}

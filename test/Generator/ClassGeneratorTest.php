<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Generator;

use DG\BypassFinals;
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
use Reinfi\OpenApiModels\Model\ClassModel;
use Reinfi\OpenApiModels\Model\Imports;

class ClassGeneratorTest extends TestCase
{
    protected function setUp(): void
    {
        BypassFinals::enable(bypassReadOnly: false);
    }

    public function testItGeneratesClassesFromOpenApi(): void
    {
        $configuration = new Configuration([], '', '');

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

        $namespace = new PhpNamespace('Test');
        $classModel = $this->getMockBuilder(ClassModel::class)
            ->setConstructorArgs(['Test', $namespace, $namespace->addClass('Test'), $this->createMock(Imports::class)])
            ->getMock();

        $transformer = $this->createMock(ClassTransformer::class);
        $transformer->expects($this->exactly(2))
            ->method('transform')
            ->with(
                $configuration,
                $openApi,
                OpenApiType::Schemas,
                self::callback(static fn (string $name): bool => in_array($name, ['Test1', 'Test2'], true)),
                self::isInstanceOf(Schema::class),
            )->willReturn($classModel);

        $namespaceResolver = $this->createMock(NamespaceResolver::class);
        $namespaceResolver->expects($this->once())
            ->method('initialize')
            ->with($configuration);

        $generator = new ClassGenerator($transformer, $namespaceResolver);

        $generator->generate($openApi, $configuration);
    }

    public function testItGeneratesRequestBodies(): void
    {
        $configuration = new Configuration([], '', '');

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

        $namespace = new PhpNamespace('Test');
        $classModel = $this->getMockBuilder(ClassModel::class)
            ->setConstructorArgs(['Test', $namespace, $namespace->addClass('Test'), $this->createMock(Imports::class)])
            ->getMock();

        $transformer = $this->createMock(ClassTransformer::class);
        $transformer->expects($this->once())
            ->method('transform')
            ->with(
                $configuration,
                $openApi,
                OpenApiType::RequestBodies,
                'Test1',
                self::isInstanceOf(Schema::class),
            )->willReturn($classModel);

        $namespaceResolver = $this->createMock(NamespaceResolver::class);
        $namespaceResolver->expects($this->once())
            ->method('initialize')
            ->with($configuration);

        $generator = new ClassGenerator($transformer, $namespaceResolver);

        $generator->generate($openApi, $configuration);
    }

    public function testItGeneratesResponses(): void
    {
        $configuration = new Configuration([], '', '');

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

        $namespace = new PhpNamespace('Test');
        $classModel = $this->getMockBuilder(ClassModel::class)
            ->setConstructorArgs(['Test', $namespace, $namespace->addClass('Test'), $this->createMock(Imports::class)])
            ->getMock();

        $transformer = $this->createMock(ClassTransformer::class);
        $transformer->expects($this->once())
            ->method('transform')
            ->with(
                $configuration,
                $openApi,
                OpenApiType::Responses,
                'Test1',
                self::isInstanceOf(Schema::class),
            )->willReturn($classModel);

        $namespaceResolver = $this->createMock(NamespaceResolver::class);
        $namespaceResolver->expects($this->once())
            ->method('initialize')
            ->with($configuration);

        $generator = new ClassGenerator($transformer, $namespaceResolver);

        $generator->generate($openApi, $configuration);
    }

    public function testItGeneratesReferenceClasses(): void
    {
        $configuration = new Configuration([], '', '');

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

        $namespace = new PhpNamespace('Test');
        $classModel = $this->getMockBuilder(ClassModel::class)
            ->setConstructorArgs(['Test', $namespace, $namespace->addClass('Test'), $this->createMock(Imports::class)])
            ->getMock();

        $transformer = $this->createMock(ClassTransformer::class);
        $transformer->expects($this->once())
            ->method('transform')
            ->with(
                $configuration,
                $openApi,
                OpenApiType::Responses,
                'Test1',
                self::isInstanceOf(Reference::class),
            )->willReturn($classModel);

        $namespaceResolver = $this->createMock(NamespaceResolver::class);
        $namespaceResolver->expects($this->once())
            ->method('initialize')
            ->with($configuration);

        $generator = new ClassGenerator($transformer, $namespaceResolver);

        $generator->generate($openApi, $configuration);
    }

    public function testItGeneratesOnlyJsonSchema(): void
    {
        $configuration = new Configuration([], '', '');

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

        $namespace = new PhpNamespace('Test');
        $classModel = $this->getMockBuilder(ClassModel::class)
            ->setConstructorArgs(['Test', $namespace, $namespace->addClass('Test'), $this->createMock(Imports::class)])
            ->getMock();

        $transformer->expects($this->once())
            ->method('transform')
            ->with(
                $configuration,
                $openApi,
                OpenApiType::Responses,
                'Test1',
                self::isInstanceOf(Schema::class),
            )->willReturn($classModel);

        $namespaceResolver = $this->createMock(NamespaceResolver::class);
        $namespaceResolver->expects($this->once())
            ->method('initialize')
            ->with($configuration);

        $generator = new ClassGenerator($transformer, $namespaceResolver);

        $generator->generate($openApi, $configuration);
    }

    public function testItThrowsExceptionIfJsonContentTypeNotFound(): void
    {
        self::expectException(OnlyJsonContentTypeSupported::class);
        self::expectExceptionMessage('Only "application/json" is supported as media type, found: application/xml');

        $configuration = new Configuration([], '', '');

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

        $generator = new ClassGenerator($transformer, $namespaceResolver);

        $generator->generate($openApi, $configuration);
    }

    public function testItSetsCommentIfTopLevelHasDescription(): void
    {
        $configuration = new Configuration([], '', '');

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

        $namespace = new PhpNamespace('Test');
        $classModel = $this->getMockBuilder(ClassModel::class)
            ->setConstructorArgs(['Test', $namespace, $namespace->addClass('Test'), $this->createMock(Imports::class)])
            ->getMock();

        $transformer = $this->createMock(ClassTransformer::class);
        $transformer->expects($this->once())
            ->method('transform')
            ->with(
                $configuration,
                $openApi,
                OpenApiType::Responses,
                'Test1',
                self::isInstanceOf(Schema::class),
            )->willReturn($classModel);

        $namespaceResolver = $this->createMock(NamespaceResolver::class);
        $namespaceResolver->expects($this->once())
            ->method('initialize')
            ->with($configuration);

        $generator = new ClassGenerator($transformer, $namespaceResolver);

        $generator->generate($openApi, $configuration);
    }
}

<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Generator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use DG\BypassFinals;
use Nette\PhpGenerator\PhpNamespace;
use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Generator\ClassGenerator;
use Reinfi\OpenApiModels\Generator\ClassTransformer;

class ClassGeneratorTest extends TestCase
{
    protected function setUp(): void
    {
        BypassFinals::enable();
    }

    public function testItGeneratesClassesFromOpenApi(): void
    {
        $configuration = new Configuration([], '', '', false);

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
        $transformer->expects($this->exactly(2))->method('transform')->with(
            $openApi,
            $this->callback(static fn (string $name): bool => in_array($name, ['Test1', 'Test2'], true)),
            $this->isInstanceOf(Schema::class),
            $this->callback(static fn (PhpNamespace $namespace): bool => $namespace->getName() === 'Schema')
        );

        $generator = new ClassGenerator($transformer);

        $namespaces = $generator->generate($openApi, $configuration);

        self::assertCount(1, $namespaces);
    }

    public function testItGeneratesClassesFromOpenApiWithNamespace(): void
    {
        $configuration = new Configuration([], '', 'Api', false);

        $openApi = new OpenApi([
            'components' => [
                'schemas' => [
                    'Test1' => [
                        'type' => 'object',
                    ],
                ],
            ],
        ]);

        $transformer = $this->createMock(ClassTransformer::class);
        $transformer->expects($this->once())->method('transform')->with(
            $openApi,
            'Test1',
            $this->isInstanceOf(Schema::class),
            $this->callback(static fn (PhpNamespace $namespace): bool => $namespace->getName() === 'Api\Schema')
        );

        $generator = new ClassGenerator($transformer);

        $namespaces = $generator->generate($openApi, $configuration);

        self::assertCount(1, $namespaces);
    }
}

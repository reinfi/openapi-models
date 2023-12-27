<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Generator;

use cebe\openapi\spec\OpenApi;
use DG\BypassFinals;
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
        $configuration = $this->createMock(Configuration::class);
        $configuration->namespace = '';

        $transformer = $this->createMock(ClassTransformer::class);
        $transformer->expects($this->exactly(2))->method('transform');

        $generator = new ClassGenerator($transformer);

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

        $generator->generate($openApi, $configuration);
    }
}

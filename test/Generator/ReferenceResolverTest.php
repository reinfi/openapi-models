<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Generator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Reference;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Generator\ReferenceResolver;

class ReferenceResolverTest extends TestCase
{
    public static function ReferenceDataProvider(): array
    {
        return [
            [
                'reference' => '#/components/schemas/TestSchema',
                'expectedName' => 'TestSchema',
            ],
            [
                'reference' => 'common.yml#/components/schemas/TestSchema',
                'expectedName' => 'TestSchema',
            ],
        ];
    }

    /**
     * @dataProvider ReferenceDataProvider
     */
    public function testItResolvesReference(string $reference, string $expectedName): void
    {
        $openApi = new OpenApi([
            'components' => [
                'schemas' => [
                    $expectedName => [
                        'type' => 'object',
                    ],
                ],
            ],
        ]);

        $reference = new Reference([
            '$ref' => $reference,
        ]);

        $resolver = new ReferenceResolver();

        self::assertEquals($expectedName, $resolver->resolve($openApi, $reference)->name);
    }

    public function testItThrowsExceptionIfNotValidReference(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Invalid reference "no-valid-reference" given, does not match pattern');

        $openApi = new OpenApi([]);
        $reference = new Reference([
            '$ref' => 'no-valid-reference',
        ]);

        $resolver = new ReferenceResolver();
        $resolver->resolve($openApi, $reference);
    }

    public function testItThrowsExceptionIfReferenceNotFound(): void
    {
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('Can not resolve reference "#/components/schemas/SchemaNotFound"');

        $openApi = new OpenApi([]);
        $reference = new Reference([
            '$ref' => '#/components/schemas/SchemaNotFound',
        ]);

        $resolver = new ReferenceResolver();
        $resolver->resolve($openApi, $reference);
    }
}

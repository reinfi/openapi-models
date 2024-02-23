<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Generator;

use InvalidArgumentException;
use openapiphp\openapi\spec\OpenApi;
use openapiphp\openapi\spec\Reference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Exception\InvalidReferenceException;
use Reinfi\OpenApiModels\Generator\OpenApiType;
use Reinfi\OpenApiModels\Generator\ReferenceResolver;

class ReferenceResolverTest extends TestCase
{
    public static function referenceDataProvider(): array
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

    #[DataProvider('referenceDataProvider')]
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
        self::assertEquals(OpenApiType::Schemas, $resolver->resolve($openApi, $reference)->openApiType);
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

    public function testItThrowsExceptionIfTypeIsNotValid(): void
    {
        self::expectException(InvalidReferenceException::class);
        self::expectExceptionMessage(
            'Reference of type "responses" is invalid, full reference: #/components/responses/ResponseInvalid'
        );

        $openApi = new OpenApi([]);
        $reference = new Reference([
            '$ref' => '#/components/responses/ResponseInvalid',
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

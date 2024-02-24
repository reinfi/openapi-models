<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\OutputFormatter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\OutputFormatter\DefaultFormatter;
use Reinfi\OpenApiModels\OutputFormatter\FormatterFactory;
use Reinfi\OpenApiModels\OutputFormatter\JUnitFormatter;
use Reinfi\OpenApiModels\OutputFormatter\OutputFormatterInterface;

class FormatterFactoryTest extends TestCase
{
    /**
     * @return iterable<array{type: string|null, expectedClass: class-string<OutputFormatterInterface>}>
     */
    public static function outputFormatterTypeDataProvider(): iterable
    {
        yield [
            'type' => null,
            'expectedClass' => DefaultFormatter::class,
        ];

        yield [
            'type' => 'default',
            'expectedClass' => DefaultFormatter::class,
        ];

        yield [
            'type' => 'unknown',
            'expectedClass' => DefaultFormatter::class,
        ];

        yield [
            'type' => 'junit',
            'expectedClass' => JUnitFormatter::class,
        ];
    }

    /**
     * @param class-string<OutputFormatterInterface> $expectedClass
     */
    #[DataProvider('outputFormatterTypeDataProvider')]
    public function testItCreatesOutputFormatter(?string $type, string $expectedClass): void
    {
        $factory = new FormatterFactory();

        self::assertInstanceOf($expectedClass, $factory->create($type));
    }
}

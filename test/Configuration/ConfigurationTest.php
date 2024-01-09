<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Configuration;

use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Configuration\Configuration;

class ConfigurationTest extends TestCase
{
    public function testItReturnsConfigurationValues(): void
    {
        $configuration = new Configuration(['path'], 'outputPath', '');

        self::assertEquals('outputPath', $configuration->outputPath);
        self::assertEmpty($configuration->namespace);
        self::assertFalse($configuration->clearOutputDirectory);
        self::assertFalse($configuration->dateTimeAsObject);
        self::assertEquals(\DateTimeInterface::RFC3339, $configuration->dateTimeFormat);
        self::assertCount(1, $configuration->paths);

        $configuration = new Configuration(['path'], 'outputPath', 'Api', true, true, 'Y-m-d', 'Y-m-d H:i:s');

        self::assertEquals('Api', $configuration->namespace);
        self::assertTrue($configuration->clearOutputDirectory);
        self::assertTrue($configuration->dateTimeAsObject);
        self::assertEquals('Y-m-d', $configuration->dateFormat);
        self::assertEquals('Y-m-d H:i:s', $configuration->dateTimeFormat);
    }
}

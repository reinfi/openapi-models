<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Configuration;

use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Configuration\Configuration;

class ConfigurationTest extends TestCase
{
    public function testItReturnsConfigurationValues(): void
    {
        $configuration = new Configuration(['path'], 'outputPath', '', false, false);

        self::assertEquals('outputPath', $configuration->outputPath);
        self::assertEmpty($configuration->namespace);
        self::assertFalse($configuration->clearOutputDirectory);
        self::assertFalse($configuration->dateTimeAsObject);
        self::assertCount(1, $configuration->paths);

        $configuration = new Configuration(['path'], 'outputPath', 'Api', true, true);

        self::assertEquals('Api', $configuration->namespace);
        self::assertTrue($configuration->clearOutputDirectory);
        self::assertTrue($configuration->dateTimeAsObject);
    }
}

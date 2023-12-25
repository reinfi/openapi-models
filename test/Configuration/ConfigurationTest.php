<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Configuration;

use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Configuration\Configuration;

class ConfigurationTest extends TestCase
{
    public function testItReturnsConfigurationValues(): void
    {
        $configuration = new Configuration(['path'], 'outputPath', '', false);

        self::assertEquals('outputPath', $configuration->outputPath);
        self::assertEmpty($configuration->namespace);
        self::assertFalse($configuration->clearOutputDirectory);
        self::assertCount(1, $configuration->paths);

        $configuration = new Configuration(['path'], 'outputPath', 'Api', true);

        self::assertEquals('Api', $configuration->namespace);
        self::assertTrue($configuration->clearOutputDirectory);
    }
}

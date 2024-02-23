<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Configuration;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Configuration\ConfigurationBuilder;
use Throwable;

class ConfigurationBuilderTest extends TestCase
{
    public static function configurationFileDataProvider(): array
    {
        return [
            'valid' => [
                'pathToConfiguration' => __DIR__ . '/../config/openapi-models.php',
            ],
            'no-array-config' => [
                'pathToConfiguration' => __DIR__ . '/../config/no-array-config.php',
                'expectedException' => new InvalidArgumentException(),
            ],
            'no-paths-defined' => [
                'pathToConfiguration' => __DIR__ . '/../config/no-paths-defined.php',
                'expectedException' => new InvalidArgumentException(),
            ],
            'invalid-output-directory' => [
                'pathToConfiguration' => __DIR__ . '/../config/invalid-output-directory.php',
                'expectedException' => new InvalidArgumentException(),
            ],
        ];
    }

    #[DataProvider('configurationFileDataProvider')]
    public function testItBuildsValidConfiguration(
        string $pathToConfiguration,
        ?Throwable $expectedException = null
    ): void {
        if ($expectedException !== null) {
            self::expectException($expectedException::class);
        }

        $builder = new ConfigurationBuilder();

        $configuration = $builder->buildFromFile($pathToConfiguration);

        self::assertInstanceOf(Configuration::class, $configuration);
    }
}

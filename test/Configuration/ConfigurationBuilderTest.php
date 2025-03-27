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
            'invalid-paths-type' => [
                'pathToConfiguration' => __DIR__ . '/../config/invalid-paths-type.php',
                'expectedException' => new InvalidArgumentException('Paths must be an array'),
            ],
            'invalid-paths-content' => [
                'pathToConfiguration' => __DIR__ . '/../config/invalid-paths-content.php',
                'expectedException' => new InvalidArgumentException('All paths must be strings'),
            ],
            'invalid-output-path-type' => [
                'pathToConfiguration' => __DIR__ . '/../config/invalid-output-path-type.php',
                'expectedException' => new InvalidArgumentException('Output path must be a string'),
            ],
            'invalid-namespace-type' => [
                'pathToConfiguration' => __DIR__ . '/../config/invalid-namespace-type.php',
                'expectedException' => new InvalidArgumentException('Namespace must be a string'),
            ],
            'invalid-date-format-type' => [
                'pathToConfiguration' => __DIR__ . '/../config/invalid-date-format-type.php',
                'expectedException' => new InvalidArgumentException('Date format must be a string'),
            ],
            'invalid-date-time-format-type' => [
                'pathToConfiguration' => __DIR__ . '/../config/invalid-date-time-format-type.php',
                'expectedException' => new InvalidArgumentException('Date time format must be a string'),
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
        } else {
            self::expectNotToPerformAssertions();
        }

        $builder = new ConfigurationBuilder();

        $builder->buildFromFile($pathToConfiguration);
    }
}

<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Acceptance;

use PHPUnit\Framework\TestCase;

class AcceptanceTest extends TestCase
{
    public function testApplicationRuns(): void
    {
        $output = shell_exec(
            sprintf('php %s generate --config %s', __DIR__ . '/../../bin/openapi-models', realpath(
                __DIR__ . '/../config/acceptance-test.php'
            ))
        );

        self::assertNotNull($output);
        self::assertNotFalse($output);

        $expectedFiles = glob(__DIR__ . '/ExpectedClasses/**/*.php');
        self::assertNotFalse($expectedFiles);

        foreach ($expectedFiles as $file) {
            $fileName = basename($file);
            $fileDirectory = basename(dirname($file));

            self::assertFileEquals($file, sprintf(__DIR__ . '/../output/%s/%s', $fileDirectory, $fileName));
        }

        self::assertFileDoesNotExist(__DIR__ . '/../output/Schema/NullableDate.php');
    }
}

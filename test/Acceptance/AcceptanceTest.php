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

        self::assertFileEquals(__DIR__ . '/ExpectedClasses/Test1.php', __DIR__ . '/../output/Schema/Test1.php');
        self::assertFileEquals(__DIR__ . '/ExpectedClasses/Test2.php', __DIR__ . '/../output/Schema/Test2.php');
        self::assertFileEquals(__DIR__ . '/ExpectedClasses/Test3.php', __DIR__ . '/../output/Schema/Test3.php');
        self::assertFileEquals(__DIR__ . '/ExpectedClasses/Test4.php', __DIR__ . '/../output/Schema/Test4.php');
        self::assertFileEquals(__DIR__ . '/ExpectedClasses/Test5.php', __DIR__ . '/../output/Schema/Test5.php');
        self::assertFileEquals(__DIR__ . '/ExpectedClasses/Test6.php', __DIR__ . '/../output/Schema/Test6.php');
        self::assertFileEquals(
            __DIR__ . '/ExpectedClasses/Test6States.php',
            __DIR__ . '/../output/Schema/Test6States.php'
        );
        self::assertFileEquals(
            __DIR__ . '/ExpectedClasses/RequestBody1.php',
            __DIR__ . '/../output/RequestBody/RequestBody1.php'
        );
        self::assertFileEquals(
            __DIR__ . '/ExpectedClasses/Response1.php',
            __DIR__ . '/../output/Response/Response1.php'
        );
        self::assertFileEquals(
            __DIR__ . '/ExpectedClasses/Response2.php',
            __DIR__ . '/../output/Response/Response2.php'
        );
    }
}

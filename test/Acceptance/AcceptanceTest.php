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

        self::assertFileEquals(__DIR__ . '/ExpectedClasses/Test1.php', __DIR__ . '/../output/Test1.php');
        self::assertFileEquals(__DIR__ . '/ExpectedClasses/Test2.php', __DIR__ . '/../output/Test2.php');
        self::assertFileEquals(__DIR__ . '/ExpectedClasses/Test3.php', __DIR__ . '/../output/Test3.php');
        self::assertFileEquals(__DIR__ . '/ExpectedClasses/Test4.php', __DIR__ . '/../output/Test4.php');
        self::assertFileEquals(__DIR__ . '/ExpectedClasses/Test5.php', __DIR__ . '/../output/Test5.php');
        self::assertFileEquals(__DIR__ . '/ExpectedClasses/Test6.php', __DIR__ . '/../output/Test6.php');
    }
}

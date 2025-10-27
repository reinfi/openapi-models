<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Acceptance;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

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

        $baseDirectoryPath = __DIR__ . '/ExpectedClasses';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDirectoryPath));
        $expectedFiles = [];
        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                $expectedFiles[] = $file->getPathname();
            }
        }

        foreach ($expectedFiles as $file) {
            $fileWithoutBaseDirectory = str_replace($baseDirectoryPath, '', $file);
            $fileName = basename($fileWithoutBaseDirectory);
            $fileDirectory = ltrim(dirname($fileWithoutBaseDirectory));

            self::assertFileEquals($file, sprintf(__DIR__ . '/../output/%s/%s', $fileDirectory, $fileName));
        }

        self::assertFileDoesNotExist(__DIR__ . '/../output/Schema/NullableDate.php');
        self::assertFileDoesNotExist(__DIR__ . '/../output/Schema/Money.php');
        self::assertFileDoesNotExist(__DIR__ . '/../output/Schema/Test7OrTest8.php');
    }
}

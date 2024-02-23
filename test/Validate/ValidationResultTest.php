<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Validate;

use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Validate\ValidationFile;
use Reinfi\OpenApiModels\Validate\ValidationFileResult;
use Reinfi\OpenApiModels\Validate\ValidationResult;

class ValidationResultTest extends TestCase
{
    public function testFlagValidIsCorrect(): void
    {
        $result = new ValidationResult();

        self::assertTrue($result->isValid());

        $result->add(new ValidationFile('', '', ValidationFileResult::Ok));
        self::assertTrue($result->isValid());

        $result->add(new ValidationFile('', '', ValidationFileResult::NotExisting));
        self::assertFalse($result->isValid());
    }

    public function testItReturnsInvalidFiles(): void
    {
        $result = new ValidationResult();

        self::assertCount(0, $result->getInvalidFiles());

        $result->add(new ValidationFile('', '', ValidationFileResult::Ok));
        self::assertCount(0, $result->getInvalidFiles());

        $result->add(new ValidationFile('', '', ValidationFileResult::NotExisting));
        $result->add(new ValidationFile('', '', ValidationFileResult::Differs));
        self::assertCount(2, $result->getInvalidFiles());
    }
}

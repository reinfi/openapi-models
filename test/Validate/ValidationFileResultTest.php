<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Validate;

use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Validate\ValidationFileResult;

class ValidationFileResultTest extends TestCase
{
    public function testIsValid(): void
    {
        self::assertTrue(ValidationFileResult::Ok->isValid());
        self::assertFalse(ValidationFileResult::NotExisting->isValid());
        self::assertFalse(ValidationFileResult::Differs->isValid());
    }
}

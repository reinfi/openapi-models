<?php

declare(strict_types=1);

namespace Api\Schema\Test;

/**
 * Test object to be in a different namespace
 */
readonly class TestNamespace
{
    public function __construct(
        public int $id,
    ) {
    }
}

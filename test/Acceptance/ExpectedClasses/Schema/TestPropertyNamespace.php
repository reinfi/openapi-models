<?php

declare(strict_types=1);

namespace Api\Schema;

use Api\Schema\Test\TestNamespace;

readonly class TestPropertyNamespace
{
    public function __construct(
        public TestNamespace $test,
    ) {
    }
}

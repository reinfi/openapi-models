<?php

declare(strict_types=1);

namespace Api\Schema;

readonly class Test5
{
    public function __construct(
        public bool $ok,
        public Test1 $test,
        public string $fullName,
    ) {
    }
}

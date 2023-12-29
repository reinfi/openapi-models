<?php

declare(strict_types=1);

namespace Api\Schema;

readonly class Test2
{
    public function __construct(
        public bool $ok,
        public Test1 $test,
    ) {
    }
}

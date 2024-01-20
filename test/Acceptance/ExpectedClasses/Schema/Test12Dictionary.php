<?php

declare(strict_types=1);

namespace Api\Schema;

readonly class Test12Dictionary
{
    public function __construct(
        public string $key,
        public int $value,
    ) {
    }
}

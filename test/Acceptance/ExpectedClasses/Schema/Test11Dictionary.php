<?php

declare(strict_types=1);

namespace Api\Schema;

readonly class Test11Dictionary
{
    public function __construct(
        public string $key,
        public float $value,
    ) {
    }
}

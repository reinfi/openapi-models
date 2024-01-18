<?php

declare(strict_types=1);

namespace Api\Schema;

readonly class Test13Dictionary
{
    public function __construct(
        public string $key,
        public mixed $value
    ) {
    }
}

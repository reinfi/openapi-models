<?php

declare(strict_types=1);

namespace Api\Schema;

readonly class Test10AllOfThem
{
    public function __construct(
        public string $name,
        public string $id,
    ) {
    }
}

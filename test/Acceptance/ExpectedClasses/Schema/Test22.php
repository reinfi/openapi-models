<?php

declare(strict_types=1);

namespace Api\Schema;

readonly class Test22
{
    public function __construct(
        public string $group,
        public string $option,
    ) {
    }
}

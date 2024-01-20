<?php

declare(strict_types=1);

namespace Api\Schema;

readonly class Test15Dictionary
{
    public function __construct(
        public string $key,
        /** @var string[] $value */
        public array $value,
    ) {
    }
}

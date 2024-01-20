<?php

declare(strict_types=1);

namespace Api\Schema;

readonly class Test14Dictionary
{
    public function __construct(
        public string $key,
        public Test14DictionaryValue $value,
    ) {
    }
}

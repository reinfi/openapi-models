<?php

declare(strict_types=1);

namespace Api\Schema;

readonly class Test13DictionaryValue
{
    public function __construct(
        public string $id,
        public ?string $name,
    ) {
    }
}

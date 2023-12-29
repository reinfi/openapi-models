<?php

declare(strict_types=1);

namespace Api\Schema;

readonly class Test1
{
    public function __construct(
        public int $id,
        public string $email,
        public bool $admin,
        public ?string $changed,
        public ?bool $deleted = null,
    ) {
    }
}

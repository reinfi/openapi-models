<?php

declare(strict_types=1);

namespace Api\Schema;

/**
 * Test1 object to show functionality
 */
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

<?php

declare(strict_types=1);

namespace Api\Schema;

readonly class Test6
{
    public function __construct(
        public string $id,
        /** @var array<Test1|Test2>|null $tests */
        public ?array $tests = null,
        /** @var Test6States[] $states */
        public array $states,
    ) {
    }
}

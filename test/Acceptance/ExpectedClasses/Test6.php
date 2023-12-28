<?php

declare(strict_types=1);
    
namespace Api;

readonly class Test6
{
    public function __construct(
        public string $id,
        /** @var array<Test1|Test2> $tests */
        public ?array $tests = null,
    ) {
    }
}

<?php

declare(strict_types=1);
    
namespace Api;

readonly class Test3
{
    public function __construct(
        public string $id,
        /** @var Test1[] $tests */
        public array $tests,
    ) {
    }
}

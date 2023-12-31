<?php

declare(strict_types=1);

namespace Api\Response;

readonly class Response1
{
    public function __construct(
        public string $id,
        public ?\Api\Schema\Test1 $test = null,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Api\RequestBody;

readonly class RequestBody1
{
    public function __construct(
        public string $id,
        public ?\Api\Schema\Test1 $test = null,
    ) {
    }
}

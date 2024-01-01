<?php

declare(strict_types=1);

namespace Api\RequestBody;

use Api\Schema\Test1;

readonly class RequestBody1
{
    public function __construct(
        public string $id,
        public ?Test1 $test = null,
    ) {
    }
}

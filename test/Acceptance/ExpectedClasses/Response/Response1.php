<?php

declare(strict_types=1);

namespace Api\Response;

use Api\Schema\Test1;
use Api\Schema\Test2;
use Api\Schema\Test3;
use Api\Schema\Test4;

/**
 * Response 1 for json requests
 */
readonly class Response1
{
    public function __construct(
        public string $id,
        public ?Test1 $test = null,
        /** @var Test2[]|null $items */
        public ?array $items = null,
        /** @var array<Test3|Test4>|null $whoKnows */
        public ?array $whoKnows = null,
    ) {
    }
}

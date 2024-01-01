<?php

declare(strict_types=1);

namespace Api\Response;

/**
 * Response 1 for json requests
 */
readonly class Response1
{
    public function __construct(
        public string $id,
        public ?\Api\Schema\Test1 $test = null,
    ) {
    }
}

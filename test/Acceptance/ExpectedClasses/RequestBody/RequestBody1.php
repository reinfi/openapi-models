<?php

declare(strict_types=1);

namespace Api\RequestBody;

use Api\Schema\Test1;
use JsonSerializable;

readonly class RequestBody1 implements JsonSerializable
{
    public function __construct(
        public string $id,
        public ?Test1 $test = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        return array_filter(
            get_object_vars($this),
            static fn (mixed $value, string $key): bool => !(in_array($key, ['test'], true) && $value === null),
            ARRAY_FILTER_USE_BOTH
        );
    }
}

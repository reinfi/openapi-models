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

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter(
            [
                'id' => $this->id,
                'test' => $this->test,
            ],
            static fn (mixed $value): bool => $value === null
        );
    }
}

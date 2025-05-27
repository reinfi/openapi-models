<?php

declare(strict_types=1);

namespace Api\Schema;

use JsonSerializable;

readonly class Test18SingleOptional implements JsonSerializable
{
    public function __construct(
        public ?int $id = null,
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
            ],
            static fn (mixed $value): bool => $value !== null
        );
    }
}

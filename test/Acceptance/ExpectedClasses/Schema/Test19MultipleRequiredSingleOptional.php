<?php

declare(strict_types=1);

namespace Api\Schema;

use JsonSerializable;

readonly class Test19MultipleRequiredSingleOptional implements JsonSerializable
{
    public function __construct(
        public int $id,
        public ?string $changed,
        public ?string $email = null,
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
                'changed' => $this->changed,
                'email' => $this->email,
            ],
            static fn (mixed $value, string $key): bool => !($key === 'email' && $value === null),
            ARRAY_FILTER_USE_BOTH
        );
    }
}

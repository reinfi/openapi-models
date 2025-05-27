<?php

declare(strict_types=1);

namespace Api\Schema;

use JsonSerializable;

readonly class Test20MultipleRequiredMultipleOptional implements JsonSerializable
{
    public function __construct(
        public int $id,
        public ?string $changed,
        public ?string $email = null,
        public ?bool $admin = null,
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
                'admin' => $this->admin,
            ],
            static fn (mixed $value, string $key): bool => !(in_array($key, [
                'email',
                'admin',
            ], true) && $value === null),
            ARRAY_FILTER_USE_BOTH
        );
    }
}

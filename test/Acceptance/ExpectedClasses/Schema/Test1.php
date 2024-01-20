<?php

declare(strict_types=1);

namespace Api\Schema;

use DateTimeInterface;
use JsonSerializable;

/**
 * Test1 object to show functionality
 */
readonly class Test1 implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $email,
        public bool $admin,
        public ?string $changed,
        public DateTimeInterface $date,
        public ?DateTimeInterface $dateTime = null,
        public ?bool $deleted = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        return array_filter(
            [
                'id' => $this->id,
                'email' => $this->email,
                'admin' => $this->admin,
                'changed' => $this->changed,
                'date' => $this->date->format('Y-m-d'),
                'dateTime' => $this->dateTime?->format('Y-m-d\TH:i:sP'),
                'deleted' => $this->deleted,
            ],
            static fn (mixed $value, string $key): bool => !(in_array($key, ['dateTime', 'deleted'], true) && $value === null),
            ARRAY_FILTER_USE_BOTH
        );
    }
}

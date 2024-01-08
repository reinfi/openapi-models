<?php

declare(strict_types=1);

namespace Api\Schema;

use DateTimeInterface;

/**
 * Test1 object to show functionality
 */
readonly class Test1 implements \JsonSerializable
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
        return array_merge(get_object_vars($this), [
            'date' => $this->date->format('Y-m-d'),
            'dateTime' => $this->dateTime?->format('Y-m-d\TH:i:sP'),
        ]);
    }
}

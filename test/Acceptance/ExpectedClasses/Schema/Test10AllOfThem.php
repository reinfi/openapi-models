<?php

declare(strict_types=1);

namespace Api\Schema;

use DateTimeInterface;
use JsonSerializable;

readonly class Test10AllOfThem implements JsonSerializable
{
    public function __construct(
        public string $name,
        public DateTimeInterface $date,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'date' => $this->date->format('Y-m-d'),
        ];
    }
}

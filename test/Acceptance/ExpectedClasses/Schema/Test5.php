<?php

declare(strict_types=1);

namespace Api\Schema;

use DateTimeInterface;
use JsonSerializable;

readonly class Test5 implements JsonSerializable
{
    public function __construct(
        public bool $ok,
        public Test1 $test,
        public int $money,
        public string $fullName,
        public ?string $address,
        public ?DateTimeInterface $date = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        return array_merge(get_object_vars($this), [
            'date' => $this->date?->format('Y-m-d'),
        ]);
    }
}

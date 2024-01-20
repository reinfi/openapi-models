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
        public float $money,
        public DateTimeInterface $date,
        public string $fullName,
        public ?string $address,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'ok' => $this->ok,
            'test' => $this->test,
            'money' => $this->money,
            'date' => $this->date->format('Y-m-d'),
            'fullName' => $this->fullName,
            'address' => $this->address,
        ];
    }
}

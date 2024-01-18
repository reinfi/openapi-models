<?php

declare(strict_types=1);

namespace Api\Schema;

use DateTimeInterface;
use JsonSerializable;

readonly class Test2 implements JsonSerializable
{
    public function __construct(
        public bool $ok,
        public Test1 $test,
        public float $money,
        public DateTimeInterface $date,
    ) {
    }

    public function jsonSerialize(): array
    {
        return array_merge(get_object_vars($this), [
            'date' => $this->date->format('Y-m-d'),
        ]);
    }
}

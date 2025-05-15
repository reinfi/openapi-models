<?php

declare(strict_types=1);

namespace Api\Schema;

use DateTimeInterface;
use JsonSerializable;

readonly class Test10 implements JsonSerializable
{
    public function __construct(
        public Test10AllOfThem $allOfThem,
        public ?DateTimeInterface $allOfOneRefDateNullable,
        public string $allOfOneString,
        public null $allOfNullValue,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'allOfThem' => $this->allOfThem,
            'allOfOneRefDateNullable' => $this->allOfOneRefDateNullable?->format('Y-m-d'),
            'allOfOneString' => $this->allOfOneString,
            'allOfNullValue' => $this->allOfNullValue,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Api\Schema;

use DateTimeInterface;
use JsonSerializable;

readonly class Test6 implements JsonSerializable
{
    public function __construct(
        public string $id,
        /** @var Test6States[] $states */
        public array $states,
        /** @var array<Test1|Test2>|null $tests */
        public ?array $tests = null,
        /** @var array<DateTimeInterface>|null $dates */
        public ?array $dates = null,
        /** @var array<string[]>|null $arrayOfArray */
        public ?array $arrayOfArray = null,
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
                'states' => $this->states,
                'tests' => $this->tests,
                'dates' => $this->dates === null ? $this->dates : array_map(static fn (DateTimeInterface $date): string => $date->format('Y-m-d'), $this->dates),
                'arrayOfArray' => $this->arrayOfArray,
            ],
            static fn (mixed $value, string $key): bool => !(in_array($key, [
                'tests',
                'dates',
                'arrayOfArray',
            ], true) && $value === null),
            ARRAY_FILTER_USE_BOTH
        );
    }
}

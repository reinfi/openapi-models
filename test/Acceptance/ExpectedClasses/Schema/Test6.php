<?php

declare(strict_types=1);

namespace Api\Schema;

use DateTimeInterface;
use JsonSerializable;

readonly class Test6 implements JsonSerializable
{
    public function __construct(
        public string $id,
        /** @var array<Test1|Test2>|null $tests */
        public ?array $tests = null,
        /** @var Test6States[] $states */
        public array $states,
        /** @var array<DateTimeInterface>|null $dates */
        public ?array $dates = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        return array_merge(get_object_vars($this), [
            'dates' => $this->dates === null ? $this->dates : array_map(static fn (DateTimeInterface $date): string => $date->format('Y-m-d'), $this->dates),
        ]);
    }
}

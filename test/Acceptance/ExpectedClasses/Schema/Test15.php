<?php

declare(strict_types=1);

namespace Api\Schema;

use JsonSerializable;

readonly class Test15 implements JsonSerializable
{
    /** @var Test15Dictionary[] */
    private array $dictionaries;

    public function __construct(Test15Dictionary ...$dictionaries)
    {
        $this->dictionaries = $dictionaries;
    }

    public function jsonSerialize(): array
    {
        return [
            ...array_map(
                /** @return string[] */
                fn (int $index): array => $this->dictionaries[$index]->value,
                array_flip(
                    array_map(
                        static fn (Test15Dictionary $dictionary): string => $dictionary->key,
                        $this->dictionaries
                    )
                )
            )
        ];
    }
}

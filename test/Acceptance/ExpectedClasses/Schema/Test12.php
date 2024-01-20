<?php

declare(strict_types=1);

namespace Api\Schema;

use JsonSerializable;

readonly class Test12 implements JsonSerializable
{
    /** @var Test12Dictionary[] */
    private array $dictionaries;

    public function __construct(Test12Dictionary ...$dictionaries)
    {
        $this->dictionaries = $dictionaries;
    }

    public function jsonSerialize(): array
    {
        return [
            ...array_map(
                fn (int $index): int => $this->dictionaries[$index]->value,
                array_flip(
                    array_map(
                        static fn (Test12Dictionary $dictionary): string => $dictionary->key,
                        $this->dictionaries
                    )
                )
            )
        ];
    }
}

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

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            ...array_map(
                /** @return array<string> */
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

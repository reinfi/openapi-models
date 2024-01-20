<?php

declare(strict_types=1);

namespace Api\Schema;

use JsonSerializable;

readonly class Test14 implements JsonSerializable
{
    /** @var Test14Dictionary[] */
    private array $dictionaries;

    public function __construct(Test14Dictionary ...$dictionaries)
    {
        $this->dictionaries = $dictionaries;
    }

    public function jsonSerialize(): array
    {
        return [
            ...array_map(
                fn (int $index): Test14DictionaryValue => $this->dictionaries[$index]->value,
                array_flip(
                    array_map(
                        static fn (Test14Dictionary $dictionary): string => $dictionary->key,
                        $this->dictionaries
                    )
                )
            )
        ];
    }
}

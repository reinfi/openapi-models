<?php

declare(strict_types=1);

namespace Api\Schema;

use JsonSerializable;

readonly class Test13 implements JsonSerializable
{
    /** @var Test13Dictionary[] */
    private array $dictionaries;

    public function __construct(Test13Dictionary ...$dictionaries)
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
                fn (int $index): Test13DictionaryValue => $this->dictionaries[$index]->value,
                array_flip(
                    array_map(
                        static fn (Test13Dictionary $dictionary): string => $dictionary->key,
                        $this->dictionaries
                    )
                )
            )
        ];
    }
}

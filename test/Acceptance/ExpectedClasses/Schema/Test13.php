<?php

declare(strict_types=1);

namespace Api\Schema;

use JsonSerializable;

readonly class Test13 implements JsonSerializable
{
    /**
     * @var Test13Dictionary[]
     */
    private array $dictionary;

    public function __construct(
        Test13Dictionary ...$dictionaries
    ) {
        $this->dictionary = $dictionaries;
    }

    public function jsonSerialize(): array
    {
        return array_map(
            fn (int $index): mixed => $this->dictionary[$index]->value,
            array_flip(
                array_map(
                    static fn (Test13Dictionary $dictionary): string => $dictionary->key,
                    $this->dictionary
                )
            ),
        );
    }
}

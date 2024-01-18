<?php

declare(strict_types=1);

namespace Api\Schema;

readonly class Test12
{
    /**
     * @var Test12Dictionary[]
     */
    private array $dictionary;

    public function __construct(
        Test12Dictionary ...$dictionaries
    ) {
        $this->dictionary = $dictionaries;
    }

    public function jsonSerialize(): array
    {
        return array_map(
            fn (int $index): int => $this->dictionary[$index]->value,
            array_flip(
                array_map(
                    static fn (Test13Dictionary $dictionary): string => $dictionary->key,
                    $this->dictionary
                )
            ),
        );
    }
}

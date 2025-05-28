<?php

declare(strict_types=1);

namespace Api\Schema;

use JsonSerializable;

readonly class Test11 implements JsonSerializable
{
    /** @var Test11Dictionary[] */
    private array $dictionaries;

    public function __construct(
        public string $name,
        Test11Dictionary ...$dictionaries,
    ) {
        $this->dictionaries = $dictionaries;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            ...array_map(
                fn (int $index): float => $this->dictionaries[$index]->value,
                array_flip(
                    array_map(
                        static fn (Test11Dictionary $dictionary): string => $dictionary->key,
                        $this->dictionaries
                    )
                )
            )
        ];
    }
}

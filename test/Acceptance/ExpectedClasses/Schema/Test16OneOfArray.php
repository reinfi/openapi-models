<?php

declare(strict_types=1);

namespace Api\Schema;

use JsonSerializable;

readonly class Test16OneOfArray implements JsonSerializable
{
    public function __construct(
        /** @var Test7|string[] $requiredValue */
        public Test7|array $requiredValue,
        /** @var Test8|string[]|int[]|null $noneRequiredValue */
        public Test8|array|null $noneRequiredValue = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter(
            [
                'requiredValue' => $this->requiredValue,
                'noneRequiredValue' => $this->noneRequiredValue,
            ],
            static fn (mixed $value): bool => $value !== null
        );
    }
}

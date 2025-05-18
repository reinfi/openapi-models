<?php

declare(strict_types=1);

namespace Api\Schema;

use JsonSerializable;

readonly class Test16OneOfArray implements JsonSerializable
{
    public function __construct(
        /** @var Test7|array<string> $requiredValue */
        public Test7|array $requiredValue,
        /** @var Test8|array<string>|array<int>|null $noneRequiredValue */
        public Test8|array|null $noneRequiredValue = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        return array_filter(
            [
                'requiredValue' => $this->requiredValue,
                'noneRequiredValue' => $this->noneRequiredValue,
            ],
            static fn (mixed $value, string $key): bool => !(in_array($key, ['noneRequiredValue'], true) && $value === null),
            ARRAY_FILTER_USE_BOTH
        );
    }
}

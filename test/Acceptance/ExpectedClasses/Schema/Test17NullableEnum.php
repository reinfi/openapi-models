<?php

declare(strict_types=1);

namespace Api\Schema;

readonly class Test17NullableEnum
{
    public function __construct(
        public ?Test17NullableEnumEnumValue $enumValue,
    ) {
    }
}

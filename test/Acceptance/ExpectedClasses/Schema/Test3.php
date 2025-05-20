<?php

declare(strict_types=1);

namespace Api\Schema;

use JsonSerializable;

readonly class Test3 implements JsonSerializable
{
    public function __construct(
        public string $id,
        public float $dollar,
        /** @var Test1[] $tests */
        public array $tests,
        public Test3Inline $inline,
        public ?string $name = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter(
            [
                'id' => $this->id,
                'dollar' => $this->dollar,
                'tests' => $this->tests,
                'inline' => $this->inline,
                'name' => $this->name,
            ],
            static fn (mixed $value): bool => $value !== null
        );
    }
}

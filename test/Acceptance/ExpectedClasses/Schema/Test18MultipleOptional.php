<?php

declare(strict_types=1);

namespace Api\Schema;

use DateTimeInterface;
use JsonSerializable;

readonly class Test18MultipleOptional implements JsonSerializable
{
    public function __construct(
        public ?int $id = null,
        public ?string $email = null,
        public ?bool $admin = null,
        public ?string $changed = null,
        public ?DateTimeInterface $date = null,
        public ?DateTimeInterface $dateTime = null,
        public ?bool $deleted = null,
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
                'email' => $this->email,
                'admin' => $this->admin,
                'changed' => $this->changed,
                'date' => $this->date?->format('Y-m-d'),
                'dateTime' => $this->dateTime?->format('Y-m-d\TH:i:sP'),
                'deleted' => $this->deleted,
            ],
            static fn (mixed $value): bool => $value !== null
        );
    }
}

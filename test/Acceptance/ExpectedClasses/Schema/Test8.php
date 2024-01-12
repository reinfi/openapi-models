<?php

declare(strict_types=1);

namespace Api\Schema;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use Countable;
use DateTimeInterface;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * @implements ArrayAccess<int, DateTimeInterface>
 * @implements IteratorAggregate<DateTimeInterface>
 */
readonly class Test8 implements IteratorAggregate, Countable, ArrayAccess, JsonSerializable
{
    public function __construct(
        /** @var array<DateTimeInterface>|null $items */
        private ?array $items,
    ) {
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items ?? []);
    }

    public function count(): int
    {
        return count($this->items ?? []);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): ?DateTimeInterface
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new BadMethodCallException('Object is readOnly');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new BadMethodCallException('Object is readOnly');
    }

    public function jsonSerialize(): ?array
    {
        return $this->items === null ? $this->items : array_map(static fn (DateTimeInterface $date): string => $date->format('Y-m-d'), $this->items);
    }
}

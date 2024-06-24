<?php

declare(strict_types=1);

namespace Api\Schema;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * @implements ArrayAccess<int, Test1|Test2|int[]>
 * @implements IteratorAggregate<Test1|Test2|int[]>
 */
readonly class Test9 implements IteratorAggregate, Countable, ArrayAccess, JsonSerializable
{
    /** @var array<Test1|Test2|int[]> $items */
    private array $items;

    /**
     * @param Test1|Test2|int[] ...$items
     */
    public function __construct(Test1|Test2|array ...$items)
    {
        $this->items = $items;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * @return Test1|Test2|int[]|null
     */
    public function offsetGet(mixed $offset): Test1|Test2|array|null
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

    public function jsonSerialize(): array
    {
        return $this->items;
    }
}

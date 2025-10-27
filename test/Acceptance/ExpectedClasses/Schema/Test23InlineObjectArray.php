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
 * @implements \ArrayAccess<int, Test23InlineObjectArrayItems>
 * @implements \IteratorAggregate<Test23InlineObjectArrayItems>
 */
readonly class Test23InlineObjectArray implements IteratorAggregate, Countable, ArrayAccess, JsonSerializable
{
    /** @var array<Test23InlineObjectArrayItems> $items */
    private array $items;

    public function __construct(Test23InlineObjectArrayItems ...$items)
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

    public function offsetGet(mixed $offset): ?Test23InlineObjectArrayItems
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

    /**
     * @return array<Test23InlineObjectArrayItems>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}

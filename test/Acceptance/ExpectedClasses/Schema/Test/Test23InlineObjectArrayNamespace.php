<?php

declare(strict_types=1);

namespace Api\Schema\Test;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * @implements \ArrayAccess<int, Test23InlineObjectArrayNamespaceItems>
 * @implements \IteratorAggregate<Test23InlineObjectArrayNamespaceItems>
 */
readonly class Test23InlineObjectArrayNamespace implements IteratorAggregate, Countable, ArrayAccess, JsonSerializable
{
    /** @var array<Test23InlineObjectArrayNamespaceItems> $items */
    private array $items;

    public function __construct(Test23InlineObjectArrayNamespaceItems ...$items)
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

    public function offsetGet(mixed $offset): ?Test23InlineObjectArrayNamespaceItems
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
     * @return array<Test23InlineObjectArrayNamespaceItems>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}

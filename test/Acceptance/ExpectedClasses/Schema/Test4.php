<?php

declare(strict_types=1);

namespace Api\Schema;

use DateTimeInterface;
use JsonSerializable;

readonly class Test4 implements JsonSerializable
{
    public function __construct(
        public string $id,
        public Test1|Test2 $whichTest,
        public Test1|Test4OneOfEnum1|null $oneOfEnum = null,
        public Test1|DateTimeInterface|null $oneOfDate = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        return array_merge(get_object_vars($this), [
            'oneOfDate' => $this->oneOfDate instanceOf DateTimeInterface ? $this->oneOfDate->format('Y-m-d') : $this->oneOfDate,
        ]);
    }
}

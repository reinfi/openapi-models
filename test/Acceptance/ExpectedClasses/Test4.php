<?php

declare(strict_types=1);

namespace Api;

readonly class Test4
{
    public function __construct(
        public string $id,
        public Test1|Test2 $whichTest,
        public Test1|Test4OneOfEnum1|null $oneOfEnum = null,
    ) {
    }
}

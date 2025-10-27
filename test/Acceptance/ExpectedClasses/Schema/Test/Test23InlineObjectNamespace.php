<?php

declare(strict_types=1);

namespace Api\Schema\Test;

readonly class Test23InlineObjectNamespace
{
    public function __construct(
        public Test23InlineObjectNamespaceUser $user,
    ) {
    }
}

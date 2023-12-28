<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Exception;

use Exception;

class UnresolvedArrayTypeException extends Exception
{
    public function __construct(string $type)
    {
        parent::__construct(
            sprintf('Could not resolve array type, got type "%s"', $type)
        );
    }
}

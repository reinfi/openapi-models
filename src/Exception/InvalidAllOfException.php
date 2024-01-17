<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Exception;

use Exception;

class InvalidAllOfException extends Exception
{
    public function __construct(string $propertyName, string $reason)
    {
        parent::__construct(
            sprintf('The definition for allOf in property "%s" is invalid, reason: %s', $propertyName, $reason)
        );
    }
}

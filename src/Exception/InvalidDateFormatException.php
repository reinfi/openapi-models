<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Exception;

use Exception;

class InvalidDateFormatException extends Exception
{
    public function __construct(string $propertyName)
    {
        parent::__construct(
            sprintf('Invalid date format found for property "%s"', $propertyName)
        );
    }
}

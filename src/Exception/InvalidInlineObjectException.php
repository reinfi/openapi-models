<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Exception;

use Exception;

class InvalidInlineObjectException extends Exception
{
    public function __construct(string $parentName, string $propertyName)
    {
        parent::__construct(
            sprintf('Could not transform inline object for property "%s" in class "%s"', $propertyName, $parentName)
        );
    }
}

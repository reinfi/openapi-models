<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Exception;

use Exception;

class PropertyNotFoundException extends Exception
{
    public function __construct(string $propertyName)
    {
        parent::__construct(sprintf('Property "%s" was not found in schema', $propertyName));
    }
}

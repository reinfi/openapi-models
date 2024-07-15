<?php

namespace Reinfi\OpenApiModels\Exception;

use Exception;

class InvalidEnumSchema extends Exception
{
    public function __construct(
        string $enumName,
        string $message,
    ) {
        parent::__construct(
            sprintf('Enum "%s" is invalid, reason: %s', $enumName, $message)
        );
    }
}

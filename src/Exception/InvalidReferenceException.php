<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Exception;

use Exception;

class InvalidReferenceException extends Exception
{
    public function __construct(
        string $referenceType,
        string $reference,
    ) {
        parent::__construct(
            sprintf('Reference of type "%s" is invalid, full reference: %s', $referenceType, $reference)
        );
    }
}

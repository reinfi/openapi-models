<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Exception;

use Exception;

class UnknownMediaTypeException extends Exception
{
    public function __construct(string $mediaType)
    {
        parent::__construct(
            sprintf('Unknown media type "%s"', $mediaType)
        );
    }
}

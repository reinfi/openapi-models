<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Exception;

use Exception;

class UnsupportedTypeForArrayException extends Exception
{
    public function __construct(string $type, ?string $additionalMessage = null)
    {
        if ($additionalMessage !== null) {
            parent::__construct(
                sprintf('Type "%s" is currently not supported for array definition. %s', $type, $additionalMessage)
            );
        } else {
            parent::__construct(
                sprintf('Type "%s" is currently not supported for array definition', $type)
            );
        }
    }
}

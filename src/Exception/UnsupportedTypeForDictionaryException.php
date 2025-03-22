<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Exception;

use Exception;

class UnsupportedTypeForDictionaryException extends Exception
{
    public function __construct(string $type, ?string $additionalMessage = null)
    {
        if ($additionalMessage !== null) {
            parent::__construct(
                sprintf('Type "%s" is currently not supported for dictionary definition. %s', $type, $additionalMessage)
            );
        } else {
            parent::__construct(
                sprintf('Type "%s" is currently not supported for dictionary definition', $type)
            );
        }
    }
}

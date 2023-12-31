<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Exception;

use Exception;
use Reinfi\OpenApiModels\Generator\OpenApiType;

class NotRegisteredNamespaceException extends Exception
{
    public function __construct(
        OpenApiType $openApiType
    ) {
        parent::__construct(
            sprintf('No namespace is registered for open api type %s', $openApiType->value)
        );
    }
}

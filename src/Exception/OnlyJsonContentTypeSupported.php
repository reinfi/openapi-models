<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Exception;

use Exception;

class OnlyJsonContentTypeSupported extends Exception
{
    /**
     * @param string[] $foundMediaTypes
     */
    public function __construct(array $foundMediaTypes)
    {
        parent::__construct(sprintf(
            'Only "application/json" is supported as media type, found: %s',
            join(',', $foundMediaTypes)
        ));
    }
}

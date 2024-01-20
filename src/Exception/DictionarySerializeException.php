<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Exception;

use Exception;

class DictionarySerializeException extends Exception
{
    public function __construct()
    {
        parent::__construct('Could not build serialize for dictionary property');
    }
}

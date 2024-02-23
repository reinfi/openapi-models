<?php

namespace Reinfi\OpenApiModels\Validate;

enum ValidationFileResult: string
{
    case Ok = 'ok';
    case NotExisting = 'notExisting';
    case Differs = 'differs';

    public function isValid(): bool
    {
        return $this === self::Ok;
    }
}

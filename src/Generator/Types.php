<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

enum Types: string
{
    case AnyOf = 'anyOf';
    case OneOf = 'oneOf';
    case Array = 'array';
    case Object = 'object';
    case Enum = 'enum';
}

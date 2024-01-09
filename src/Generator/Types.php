<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

enum Types: string
{
    case AnyOf = 'anyOf';
    case OneOf = 'oneOf';
    case Array = 'array';
    case Date = 'date';
    case DateTime = 'dateTime';
    case Object = 'object';
    case Enum = 'enum';
}

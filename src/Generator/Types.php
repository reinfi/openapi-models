<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

enum Types: string
{
    case AllOf = 'allOf';
    case AnyOf = 'anyOf';
    case OneOf = 'oneOf';
    case Array = 'array';
    case Date = 'date';
    case DateTime = 'dateTime';
    case Null = 'null';
    case Object = 'object';
    case Enum = 'enum';
}

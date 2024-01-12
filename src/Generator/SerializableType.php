<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

enum SerializableType: string
{
    case ArrayObject = 'arrayObject';
    case DateTime = 'dateTime';
    case ArrayObjectDateTime = 'arrayObjectDateTime';
    case None = 'none';
}

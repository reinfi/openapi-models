<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Serialization;

enum SerializableType: string
{
    case DateTime = 'dateTime';
    case Dictionary = 'dictionary';
    case None = 'none';
}

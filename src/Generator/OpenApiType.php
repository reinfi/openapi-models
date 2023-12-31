<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

enum OpenApiType: string
{
    case Schemas = 'schemas';
    case RequestBodies = 'requestBodies';
    case Responses = 'responses';
}

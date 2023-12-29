<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

enum OpenApiType
{
    case Schemas;
    case RequestBodies;
    case Responses;
}

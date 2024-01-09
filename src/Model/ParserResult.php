<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Model;

use cebe\openapi\spec\OpenApi;
use Mthole\OpenApiMerge\FileHandling\File;

readonly class ParserResult
{
    public function __construct(
        public OpenApi $openApi,
        /** @var File[] $parsedFiles */
        public array $parsedFiles,
    ) {
    }
}

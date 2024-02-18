<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Model;

use Mthole\OpenApiMerge\FileHandling\File;
use openapiphp\openapi\spec\OpenApi;

readonly class ParserResult
{
    public function __construct(
        public OpenApi $openApi,
        /** @var File[] $parsedFiles */
        public array $parsedFiles,
    ) {
    }
}

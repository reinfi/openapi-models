<?php

declare(strict_types=1);

use Mthole\OpenApiMerge\Merge\ComponentsMerger;
use Mthole\OpenApiMerge\Merge\PathMerger;
use Mthole\OpenApiMerge\Merge\ReferenceNormalizer;
use Mthole\OpenApiMerge\Merge\SecurityPathMerger;
use Mthole\OpenApiMerge\OpenApiMerge;
use Mthole\OpenApiMerge\Reader\FileReader;

return [
    OpenApiMerge::class => static fn () => new OpenApiMerge(
        new FileReader(),
        [new PathMerger(), new ComponentsMerger(), new SecurityPathMerger()],
        new ReferenceNormalizer()
    ),
];

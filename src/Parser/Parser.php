<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Parser;

use cebe\openapi\spec\OpenApi;
use InvalidArgumentException;
use Mthole\OpenApiMerge\FileHandling\File;
use Mthole\OpenApiMerge\OpenApiMerge;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Webmozart\Glob\Glob;

readonly class Parser
{
    public function __construct(
        private OpenApiMerge $openApiMerge
    ) {
    }

    public function parse(Configuration $configuration): OpenApi
    {
        $filesToParse = [];

        foreach ($configuration->paths as $fileOrDirectory) {
            if (is_file($fileOrDirectory)) {
                $filesToParse[] = $fileOrDirectory;
            } elseif (is_dir($fileOrDirectory)) {
                $filesToParse = array_merge(
                    $filesToParse,
                    Glob::glob(sprintf('%s/*.yml', $fileOrDirectory)) ?: [],
                    Glob::glob(sprintf('%s/*.yaml', $fileOrDirectory)) ?: [],
                    Glob::glob(sprintf('%s/*.json', $fileOrDirectory)) ?: [],
                );
            }
        }

        return $this->mergeFiles(array_map(static fn (string $file): File => new File($file), $filesToParse));
    }

    /**
     * @param File[] $files
     */
    private function mergeFiles(array $files): OpenApi
    {
        $firstFile = array_pop($files);

        if (! $firstFile instanceof File) {
            throw new InvalidArgumentException('No files found to generate models');
        }

        return $this->openApiMerge->mergeFiles($firstFile, $files, false)->getOpenApi();
    }
}

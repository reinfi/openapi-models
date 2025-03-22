<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Parser;

use InvalidArgumentException;
use Mthole\OpenApiMerge\FileHandling\File;
use Mthole\OpenApiMerge\OpenApiMerge;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Model\ParserResult;
use Webmozart\Glob\Glob;

readonly class Parser
{
    public function __construct(
        private OpenApiMerge $openApiMerge
    ) {
    }

    public function parse(Configuration $configuration): ParserResult
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
     * @param array<array-key, File> $files
     */
    private function mergeFiles(array $files): ParserResult
    {
        $firstFile = array_pop($files);

        if (! $firstFile instanceof File) {
            throw new InvalidArgumentException('No files found to generate models');
        }

        return new ParserResult($this->openApiMerge->mergeFiles($firstFile, $files, false)->getOpenApi(), [
            $firstFile,
            ...$files,
        ]);
    }
}

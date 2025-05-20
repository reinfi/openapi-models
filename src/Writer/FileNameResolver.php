<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Writer;

use Nette\PhpGenerator\ClassLike;
use Nette\PhpGenerator\PhpNamespace;
use Reinfi\OpenApiModels\Configuration\Configuration;

class FileNameResolver
{
    public function resolve(Configuration $configuration, PhpNamespace $namespace, ClassLike $class): string
    {
        $fullNamespace = $namespace->getName();
        $baseNamespace = rtrim($configuration->namespace, '\\');

        // Remove base namespace from the full namespace.
        if (str_starts_with($fullNamespace, $baseNamespace)) {
            $subNamespace = substr($fullNamespace, strlen($baseNamespace));
        } else {
            $subNamespace = $fullNamespace;
        }

        // Clean up leading/trailing backslashes and convert to directory structure.
        $subNamespace = trim($subNamespace, '\\');
        $subNamespacePath = $subNamespace !== '' ? str_replace('\\', DIRECTORY_SEPARATOR, $subNamespace) : '';

        // Build the output directory path.
        $outputDirectory = rtrim($configuration->outputPath, DIRECTORY_SEPARATOR);
        $outputDirectoryWithNamespace = $subNamespacePath !== ''
            ? $outputDirectory . DIRECTORY_SEPARATOR . $subNamespacePath
            : $outputDirectory;

        if (! is_dir($outputDirectoryWithNamespace)) {
            mkdir($outputDirectoryWithNamespace, recursive: true);
        }

        return sprintf('%s/%s.php', $outputDirectoryWithNamespace, $class->getName());
    }
}

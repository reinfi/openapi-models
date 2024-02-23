<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Writer;

use Nette\PhpGenerator\ClassLike;
use Nette\PhpGenerator\Helpers;
use Nette\PhpGenerator\PhpNamespace;
use Reinfi\OpenApiModels\Configuration\Configuration;

class FileNameResolver
{
    public function resolve(Configuration $configuration, PhpNamespace $namespace, ClassLike $class): string
    {
        $namespaceShortName = Helpers::extractShortName($namespace->getName());
        $outputDirectoryWithNamespace = sprintf('%s/%s', $configuration->outputPath, $namespaceShortName);

        if (! is_dir($outputDirectoryWithNamespace)) {
            mkdir($outputDirectoryWithNamespace);
        }

        return sprintf('%s/%s.php', $outputDirectoryWithNamespace, $class->getName());
    }
}

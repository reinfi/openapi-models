<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Writer;

use DirectoryIterator;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use Reinfi\OpenApiModels\Configuration\Configuration;

readonly class ClassWriter
{
    public function __construct(
        private PsrPrinter $printer
    ) {
    }

    public function write(Configuration $configuration, PhpNamespace $namespace): void
    {
        if ($configuration->clearOutputDirectory) {
            $this->clearOutputDirectory($configuration->outputPath);
        }

        foreach ($namespace->getClasses() as $class) {
            if ($class->getName() === null) {
                continue;
            }

            $filePath = sprintf('%s/%s.php', $configuration->outputPath, $class->getName());

            $classOnlyNamespace = new PhpNamespace($namespace->getName());
            $classOnlyNamespace->add($class);

            file_put_contents(
                $filePath,
                <<<TPL
                <?php

                declare(strict_types=1);
                    
                {$this->printer->printNamespace($classOnlyNamespace)}
                TPL
            );
        }
    }

    private function clearOutputDirectory(string $outputDirectory): void
    {
        /** @var DirectoryIterator $fileInfo */
        foreach (new DirectoryIterator($outputDirectory) as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            $filePath = $fileInfo->getRealPath();

            if (is_string($filePath)) {
                unlink($fileInfo->getRealPath());
            }
        }
    }
}

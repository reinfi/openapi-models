<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Writer;

use DirectoryIterator;
use Nette\PhpGenerator\Helpers;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use Reinfi\OpenApiModels\Configuration\Configuration;

readonly class ClassWriter
{
    public function __construct(
        private PsrPrinter $printer
    ) {
    }

    /**
     * @param array<string, PhpNamespace> $namespaces
     */
    public function write(Configuration $configuration, array $namespaces): void
    {
        if ($configuration->clearOutputDirectory) {
            $this->clearOutputDirectory($configuration->outputPath);
        }

        foreach ($namespaces as $namespace) {
            foreach ($namespace->getClasses() as $class) {
                if ($class->getName() === null) {
                    continue;
                }

                $namespaceShortName = Helpers::extractShortName($namespace->getName());
                $outputDirectoryWithNamespace = sprintf('%s/%s', $configuration->outputPath, $namespaceShortName);

                if (! is_dir($outputDirectoryWithNamespace)) {
                    mkdir($outputDirectoryWithNamespace);
                }

                $filePath = sprintf('%s/%s.php', $outputDirectoryWithNamespace, $class->getName());

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
    }

    private function clearOutputDirectory(string $outputDirectory): void
    {
        /** @var DirectoryIterator $fileInfo */
        foreach (new DirectoryIterator($outputDirectory) as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            $filePath = $fileInfo->getRealPath();

            if (is_string($filePath) && $fileInfo->isFile()) {
                unlink($fileInfo->getRealPath());
            }
        }
    }
}

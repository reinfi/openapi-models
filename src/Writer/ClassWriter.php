<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Writer;

use DirectoryIterator;
use Nette\PhpGenerator\ClassType;
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
                foreach ($namespace->getUses() as $use) {
                    if ($class instanceof ClassType && $use === $class->getExtends()) {
                        $classOnlyNamespace->addUse($use);
                    }

                    if (! $class->hasMethod('__construct')) {
                        continue;
                    }

                    foreach ($class->getMethod('__construct')->getParameters() as $parameter) {
                        if ($parameter->getType(true)?->allows($use) || $parameter->getType() === $use) {
                            $classOnlyNamespace->addUse($use);
                            continue;
                        }

                        if ($parameter->getType() === 'array' && $parameter->getComment() !== null) {
                            if (str_contains($parameter->getComment(), $use)) {
                                $classOnlyNamespace->addUse($use);
                                $parameter->setComment(
                                    str_replace($use, $namespace->simplifyName($use), $parameter->getComment())
                                );
                            }
                        }
                    }
                }
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

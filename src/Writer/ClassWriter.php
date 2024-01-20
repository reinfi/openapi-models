<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Writer;

use DirectoryIterator;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Helpers;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Property;
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

                foreach ($namespace->getUses() as $use) {
                    if ($class instanceof ClassType && $use === $class->getExtends()) {
                        $classOnlyNamespace->addUse($use);
                    }

                    if ($class instanceof ClassType && in_array($use, $class->getImplements(), true)) {
                        $classOnlyNamespace->addUse($use);
                    }

                    foreach ($class->getMethods() as $method) {
                        if ($method->getReturnType() !== 'mixed' && $method->getReturnType(true)?->allows($use)) {
                            $classOnlyNamespace->addUse($use);
                        }

                        foreach ($method->getParameters() as $parameter) {
                            $this->resolveUsagesForParameterOrProperty($classOnlyNamespace, $use, $parameter);
                        }

                        if (str_contains($method->getBody(), $use)) {
                            $classOnlyNamespace->addUse($use);
                        }
                    }

                    if ($class instanceof ClassType) {
                        foreach ($class->getProperties() as $property) {
                            $this->resolveUsagesForParameterOrProperty($classOnlyNamespace, $use, $property);
                        }
                    }
                }

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

            if (! is_string($filePath)) {
                continue;
            }

            if ($fileInfo->isDir()) {
                $this->clearOutputDirectory($filePath);
                continue;
            }

            if ($fileInfo->isFile()) {
                unlink($fileInfo->getRealPath());
            }
        }
    }

    private function resolveUsagesForParameterOrProperty(
        PhpNamespace $namespace,
        string $use,
        Parameter|Property $parameterOrProperty
    ): void {
        if ($parameterOrProperty->getType() === 'mixed') {
            return;
        }

        if ($parameterOrProperty->getType(true)?->allows($use) || $parameterOrProperty->getType() === $use) {
            $namespace->addUse($use);
        }

        if ($parameterOrProperty->getType() === 'array' && $parameterOrProperty->getComment() !== null) {
            if (str_contains($parameterOrProperty->getComment(), $use)) {
                $namespace->addUse($use);
                $parameterOrProperty->setComment(
                    str_replace($use, $namespace->simplifyName($use), $parameterOrProperty->getComment())
                );
            }
        }
    }
}

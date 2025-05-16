<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Writer;

use DirectoryIterator;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Model\ClassModel;

readonly class ClassWriter
{
    public function __construct(
        private FileNameResolver $fileNameResolver,
        private SingleNamespaceResolver $singleNamespaceResolver,
        private TemplateResolver $templateResolver,
    ) {
    }

    /**
     * @param ClassModel[] $models
     */
    public function write(Configuration $configuration, array $models): void
    {
        if ($configuration->clearOutputDirectory) {
            $this->clearOutputDirectory($configuration->outputPath);
        }

        foreach ($models as $model) {
            $namespace = $model->namespace;
            foreach ($namespace->getClasses() as $class) {
                if ($class->getName() === null) {
                    continue;
                }

                $filePath = $this->fileNameResolver->resolve($configuration, $namespace, $class);

                if (! is_dir(dirname($filePath))) {
                    mkdir(dirname($filePath));
                }

                $classOnlyNamespace = $this->singleNamespaceResolver->resolve($namespace, $class);

                file_put_contents($filePath, $this->templateResolver->resolve($classOnlyNamespace));
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
}

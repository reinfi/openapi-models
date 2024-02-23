<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Validate;

use Nette\PhpGenerator\PhpNamespace;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Writer\FileNameResolver;
use Reinfi\OpenApiModels\Writer\SingleNamespaceResolver;
use Reinfi\OpenApiModels\Writer\TemplateResolver;

class Validator
{
    public function __construct(
        private readonly FileNameResolver $fileNameResolver,
        private readonly SingleNamespaceResolver $singleNamespaceResolver,
        private readonly TemplateResolver $templateResolver,
    ) {
    }

    /**
     * @param array<string, PhpNamespace> $namespaces
     */
    public function validate(Configuration $configuration, array $namespaces): ValidationResult
    {
        $result = new ValidationResult();

        foreach ($namespaces as $namespace) {
            foreach ($namespace->getClasses() as $class) {
                if ($class->getName() === null) {
                    continue;
                }

                $filePath = $this->fileNameResolver->resolve($configuration, $namespace, $class);

                $classOnlyNamespace = $this->singleNamespaceResolver->resolve($namespace, $class);

                if (! file_exists($filePath)) {
                    $result->add(
                        new ValidationFile($class->getName(), $filePath, ValidationFileResult::NotExisting)
                    );
                    continue;
                }

                $contents = file_get_contents($filePath);

                if ($contents === false) {
                    $result->add(
                        new ValidationFile($class->getName(), $filePath, ValidationFileResult::NotExisting)
                    );
                    continue;
                }

                $result->add(
                    new ValidationFile(
                        $class->getName(),
                        $filePath,
                        $this->templateResolver->resolve(
                            $classOnlyNamespace
                        ) === $contents ? ValidationFileResult::Ok : ValidationFileResult::Differs
                    )
                );
            }
        }

        return $result;
    }
}

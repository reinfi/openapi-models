<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Validate;

use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Model\ClassModel;
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
     * @param ClassModel[] $models
     */
    public function validate(Configuration $configuration, array $models): ValidationResult
    {
        $result = new ValidationResult();

        foreach ($models as $model) {
            $namespace = $model->namespace;
            $class = $model->class;
            if ($class->getName() === null) {
                continue;
            }

            $filePath = $this->fileNameResolver->resolve($configuration, $namespace, $class);

            $classOnlyNamespace = $this->singleNamespaceResolver->resolve($namespace, $class);

            if (! file_exists($filePath)) {
                $result->add(new ValidationFile($class->getName(), $filePath, ValidationFileResult::NotExisting));
                continue;
            }

            $contents = file_get_contents($filePath);

            if ($contents === false) {
                $result->add(new ValidationFile($class->getName(), $filePath, ValidationFileResult::NotExisting));
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

        return $result;
    }
}

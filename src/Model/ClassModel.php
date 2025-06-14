<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Model;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\PhpNamespace;

class ClassModel
{
    /**
     * @var ClassModel[]
     */
    private array $inlineModels = [];

    public function __construct(
        public readonly string $className,
        public readonly PhpNamespace $namespace,
        public readonly ClassType|EnumType $class,
        public readonly Imports $imports,
    ) {
    }

    /**
     * @return ClassModel[]
     */
    public function getInlineModels(): array
    {
        return $this->inlineModels;
    }

    public function addInlineModel(self $classModel): void
    {
        $this->inlineModels[] = $classModel;

        foreach ($classModel->getInlineModels() as $inlineModel) {
            $this->addInlineModel($inlineModel);
        }
    }
}

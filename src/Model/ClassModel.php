<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Model;

use Nette\PhpGenerator\ClassLike;
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
        public readonly ClassLike $class,
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
    }
}

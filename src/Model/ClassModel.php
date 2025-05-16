<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Model;

use Nette\PhpGenerator\ClassLike;
use Nette\PhpGenerator\PhpNamespace;

readonly class ClassModel
{
    public function __construct(
        public PhpNamespace $namespace,
        public ClassLike $class,
        public Imports $imports,
    ) {
    }
}

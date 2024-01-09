<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Model;

use Nette\PhpGenerator\PhpNamespace;

class Imports
{
    /**
     * @var string[]
     */
    private array $imports = [];

    public function __construct(
        public readonly PhpNamespace $namespace
    ) {
    }

    public function addImport(string $import): void
    {
        if (in_array($import, $this->imports, true)) {
            return;
        }

        $this->imports[] = $import;
    }

    public function copyImports(): void
    {
        foreach ($this->imports as $import) {
            $this->namespace->addUse($import);
        }
    }
}

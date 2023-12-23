<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Writer;

use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\TextUI\Configuration\Php;
use Reinfi\OpenApiModels\Configuration\Configuration;

readonly class ClassWriter
{
    public function __construct(
        private PsrPrinter $printer
    ) {
    }

    public function write(Configuration $configuration, PhpNamespace $namespace): void
    {
        foreach ($namespace->getClasses() as $class) {
            if ($class->getName() === null) {
                continue;
            }

            $filePath = sprintf('%s/%s.php', $configuration->outputPath, $class->getName());

            $classOnlyNamespace = new PhpNamespace($namespace->getName());
            $classOnlyNamespace->add($class);

            file_put_contents(
                $filePath,
                <<<TPL
                <?php
                    
                {$this->printer->printNamespace($classOnlyNamespace)}
                TPL
            );
        }
    }
}

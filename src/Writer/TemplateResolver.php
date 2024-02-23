<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Writer;

use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;

class TemplateResolver
{
    public function __construct(
        private PsrPrinter $printer,
    ) {
    }

    public function resolve(PhpNamespace $namespace): string
    {
        return <<<TPL
        <?php

        declare(strict_types=1);

        {$this->printer->printNamespace($namespace)}
        TPL;
    }
}

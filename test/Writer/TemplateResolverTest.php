<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Writer;

use DG\BypassFinals;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Writer\TemplateResolver;

class TemplateResolverTest extends TestCase
{
    protected function setUp(): void
    {
        BypassFinals::enable();
    }

    public function testItResolvesTemplate(): void
    {
        $namespace = new PhpNamespace('Schema');

        $printer = $this->createMock(PsrPrinter::class);
        $printer->expects($this->once())
            ->method('printNamespace')
            ->with($namespace)
            ->willReturn('Foo');

        $resolver = new TemplateResolver($printer);

        self::assertEquals(
            <<<TPL
            <?php

            declare(strict_types=1);

            Foo
            TPL
            ,
            $resolver->resolve($namespace)
        );
    }
}

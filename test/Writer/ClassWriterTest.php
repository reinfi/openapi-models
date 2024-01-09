<?php

declare(strict_types=1);

namespace Writer;

use DG\BypassFinals;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Writer\ClassWriter;

class ClassWriterTest extends TestCase
{
    private vfsStreamDirectory $outputDir;

    protected function setUp(): void
    {
        BypassFinals::enable();

        $this->outputDir = vfsStream::setup('output');
    }

    public function testItWritesClasses(): void
    {
        $printer = $this->createMock(PsrPrinter::class);
        $printer->expects($this->exactly(2))->method('printNamespace')->willReturn('here comes class contents');

        $writer = new ClassWriter($printer);

        $configuration = new Configuration([], $this->outputDir->url(), '');

        $firstNamespace = new PhpNamespace('Schema');
        $firstNamespace->addClass('ClassFirst');

        $secondNamespace = new PhpNamespace('Response');
        $secondNamespace->addClass('ClassSecond');

        $writer->write($configuration, [
            'schemas' => $firstNamespace,
            'responses' => $secondNamespace,
        ]);

        self::assertCount(2, $this->outputDir->getChildren());
    }

    public function testItCopiesUseStatements(): void
    {
        $printer = $this->createMock(PsrPrinter::class);
        $printer->expects($this->once())->method('printNamespace')
            ->with($this->callback(
                static fn (PhpNamespace $namespace): bool => count($namespace->getClasses()) === 1 && count(
                    $namespace->getUses()
                ) === 1
            ))
            ->willReturn('here comes class contents');

        $writer = new ClassWriter($printer);

        $configuration = new Configuration([], $this->outputDir->url(), '');

        $namespace = new PhpNamespace('Schema');
        $namespace->addUse('ClassSecond');
        $class = $namespace->addClass('ClassFirst');
        $class->addMethod('__construct')->addPromotedParameter('second')->setType('ClassSecond');

        $writer->write($configuration, [
            'schemas' => $namespace,
        ]);

        self::assertCount(1, $this->outputDir->getChildren());
    }
}

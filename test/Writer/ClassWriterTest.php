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
        $printer = $this->createConfiguredMock(PsrPrinter::class, [
            'printNamespace' => 'here comes class contents',
        ]);

        $writer = new ClassWriter($printer);

        $configuration = new Configuration([], $this->outputDir->url(), '', false);

        $namespace = new PhpNamespace($configuration->namespace);
        $namespace->addClass('ClassFirst');
        $namespace->addClass('ClassSecond');

        $writer->write($configuration, $namespace);

        self::assertCount(2, $this->outputDir->getChildren());
    }
}

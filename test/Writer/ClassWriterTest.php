<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Writer;

use DG\BypassFinals;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Model\ClassModel;
use Reinfi\OpenApiModels\Model\Imports;
use Reinfi\OpenApiModels\Writer\ClassWriter;
use Reinfi\OpenApiModels\Writer\FileNameResolver;
use Reinfi\OpenApiModels\Writer\SingleNamespaceResolver;
use Reinfi\OpenApiModels\Writer\TemplateResolver;

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
        $fileNameResolver = $this->createMock(FileNameResolver::class);
        $singleNamespaceResolver = $this->createMock(SingleNamespaceResolver::class);
        $templateResolver = $this->createMock(TemplateResolver::class);

        $fileNameResolver->expects($this->exactly(2))
            ->method('resolve')
            ->willReturn(
                sprintf('%s/Schema/ClassFirst.php', $this->outputDir->url()),
                sprintf('%s/Response/ClassSecond.php', $this->outputDir->url()),
            );

        $singleNamespaceResolver->expects($this->exactly(2))
            ->method('resolve')
            ->willReturn(new PhpNamespace('Combined'));

        $templateResolver->expects($this->exactly(2))
            ->method('resolve')
            ->willReturn('ClassFirst', 'ClassSecond');

        $writer = new ClassWriter($fileNameResolver, $singleNamespaceResolver, $templateResolver);

        $configuration = new Configuration([], $this->outputDir->url(), '');

        $firstNamespace = new PhpNamespace('Schema');
        $firstClass = new ClassType('ClassFirst');
        $firstNamespace->add($firstClass);
        $firstImports = new Imports($firstNamespace);
        $firstModel = new ClassModel('ClassFirst', $firstNamespace, $firstClass, $firstImports);

        $secondNamespace = new PhpNamespace('Response');
        $secondClass = new ClassType('ClassSecond');
        $secondNamespace->add($secondClass);
        $secondImports = new Imports($secondNamespace);
        $secondModel = new ClassModel('ClassSecond', $secondNamespace, $secondClass, $secondImports);

        $writer->write($configuration, [$firstModel, $secondModel]);

        $children = $this->outputDir->getChildren();
        self::assertCount(2, $children, 'two directories should be created');

        foreach ($children as $child) {
            self::assertInstanceOf(vfsStreamDirectory::class, $child);
            $subChildren = $child->getChildren();
            self::assertCount(1, $subChildren, 'one file should exist in directory');
        }
    }

    public function testFileContentMatches(): void
    {
        $fileNameResolver = $this->createMock(FileNameResolver::class);
        $singleNamespaceResolver = $this->createMock(SingleNamespaceResolver::class);
        $templateResolver = $this->createMock(TemplateResolver::class);

        $fileNameResolver->expects($this->once())
            ->method('resolve')
            ->willReturn(sprintf('%s/Schema/ClassFirst.php', $this->outputDir->url()));

        $singleNamespaceResolver->expects($this->once())
            ->method('resolve')
            ->willReturn(new PhpNamespace('Combined'));

        $templateResolver->expects($this->once())
            ->method('resolve')
            ->willReturn('ClassFirst');

        $writer = new ClassWriter($fileNameResolver, $singleNamespaceResolver, $templateResolver);

        $configuration = new Configuration([], $this->outputDir->url(), '');

        $namespace = new PhpNamespace('Schema');
        $class = new ClassType('ClassFirst');
        $namespace->add($class);
        $imports = new Imports($namespace);
        $model = new ClassModel('ClassFirst', $namespace, $class, $imports);

        $writer->write($configuration, [$model]);

        $schemaDirectory = $this->outputDir->getChild('Schema');
        self::assertInstanceOf(vfsStreamDirectory::class, $schemaDirectory);

        $classFile = $schemaDirectory->getChild('ClassFirst.php');
        self::assertInstanceOf(vfsStreamFile::class, $classFile);

        self::assertEquals('ClassFirst', $classFile->getContent());
    }
}

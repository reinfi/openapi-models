<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Validate;

use Nette\PhpGenerator\PhpNamespace;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Model\ClassModel;
use Reinfi\OpenApiModels\Model\Imports;
use Reinfi\OpenApiModels\Validate\Validator;
use Reinfi\OpenApiModels\Writer\FileNameResolver;
use Reinfi\OpenApiModels\Writer\SingleNamespaceResolver;
use Reinfi\OpenApiModels\Writer\TemplateResolver;

class ValidatorTest extends TestCase
{
    private vfsStreamDirectory $outputDir;

    protected function setUp(): void
    {
        $this->outputDir = vfsStream::setup('output');
    }

    public function testItIsValidIfNoNamespacesArePresent(): void
    {
        $fileNameResolver = $this->createMock(FileNameResolver::class);
        $singleNamespaceResolver = $this->createMock(SingleNamespaceResolver::class);
        $templateResolver = $this->createMock(TemplateResolver::class);

        $validator = new Validator($fileNameResolver, $singleNamespaceResolver, $templateResolver);

        $configuration = new Configuration([], $this->outputDir->url(), '');

        $result = $validator->validate($configuration, []);

        self::assertTrue($result->isValid());
        self::assertCount(0, $result->getInvalidFiles());
    }

    public function testItIsValidIfClassIsEqual(): void
    {
        $schemaDirectory = new vfsStreamDirectory('Schema');
        $schemaDirectory->addChild((new vfsStreamFile('Foo.php'))->setContent('Bar'));
        $this->outputDir->addChild($schemaDirectory);

        $namespace = new PhpNamespace('Schema');
        $class = $namespace->addClass('Foo');

        $fileNameResolver = $this->createMock(FileNameResolver::class);
        $singleNamespaceResolver = $this->createMock(SingleNamespaceResolver::class);
        $templateResolver = $this->createMock(TemplateResolver::class);

        $fileNameResolver->expects($this->once())
            ->method('resolve')
            ->willReturn(sprintf('%s/Schema/Foo.php', $this->outputDir->url()));
        $singleNamespaceResolver->expects($this->once())
            ->method('resolve')
            ->willReturn($namespace);
        $templateResolver->expects($this->once())
            ->method('resolve')
            ->willReturn('Bar');

        $validator = new Validator($fileNameResolver, $singleNamespaceResolver, $templateResolver);

        $configuration = new Configuration([], $this->outputDir->url(), '');

        $result = $validator->validate($configuration, [
            new ClassModel('Foo', $namespace, $class, new Imports($namespace)),
        ]);

        self::assertTrue($result->isValid());
        self::assertCount(0, $result->getInvalidFiles());
    }

    public function testItIsNotValidIfClassDoesNotExists(): void
    {
        $namespace = new PhpNamespace('Schema');
        $class = $namespace->addClass('Foo');

        $fileNameResolver = $this->createMock(FileNameResolver::class);
        $singleNamespaceResolver = $this->createMock(SingleNamespaceResolver::class);
        $templateResolver = $this->createMock(TemplateResolver::class);

        $fileNameResolver->expects($this->once())
            ->method('resolve')
            ->willReturn(sprintf('%s/Schema/Foo.php', $this->outputDir->url()));
        $singleNamespaceResolver->expects($this->once())
            ->method('resolve')
            ->willReturn($namespace);
        $templateResolver->expects($this->never())
            ->method('resolve');

        $validator = new Validator($fileNameResolver, $singleNamespaceResolver, $templateResolver);

        $configuration = new Configuration([], $this->outputDir->url(), '');

        $result = $validator->validate($configuration, [
            new ClassModel('Foo', $namespace, $class, new Imports($namespace)),
        ]);

        self::assertFalse($result->isValid());
        self::assertCount(1, $result->getInvalidFiles());
    }

    public function testItIsNotValidIfClassDiffers(): void
    {
        $schemaDirectory = new vfsStreamDirectory('Schema');
        $schemaDirectory->addChild((new vfsStreamFile('Foo.php'))->setContent('Baz'));
        $this->outputDir->addChild($schemaDirectory);

        $namespace = new PhpNamespace('Schema');
        $class = $namespace->addClass('Foo');

        $fileNameResolver = $this->createMock(FileNameResolver::class);
        $singleNamespaceResolver = $this->createMock(SingleNamespaceResolver::class);
        $templateResolver = $this->createMock(TemplateResolver::class);

        $fileNameResolver->expects($this->once())
            ->method('resolve')
            ->willReturn(sprintf('%s/Schema/Foo.php', $this->outputDir->url()));
        $singleNamespaceResolver->expects($this->once())
            ->method('resolve')
            ->willReturn($namespace);
        $templateResolver->expects($this->once())
            ->method('resolve')
            ->willReturn('Bar');

        $validator = new Validator($fileNameResolver, $singleNamespaceResolver, $templateResolver);

        $configuration = new Configuration([], $this->outputDir->url(), '');

        $result = $validator->validate($configuration, [
            new ClassModel('Foo', $namespace, $class, new Imports($namespace)),
        ]);

        self::assertFalse($result->isValid());
        self::assertCount(1, $result->getInvalidFiles());
    }
}

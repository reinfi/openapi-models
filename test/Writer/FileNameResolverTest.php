<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Test\Writer;

use Nette\PhpGenerator\PhpNamespace;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Writer\FileNameResolver;

class FileNameResolverTest extends TestCase
{
    private vfsStreamDirectory $outputDir;

    protected function setUp(): void
    {
        $this->outputDir = vfsStream::setup('output');
    }

    public function testItResolvesToFileName(): void
    {
        $resolver = new FileNameResolver();

        $configuration = new Configuration([], $this->outputDir->url(), '');

        $namespace = new PhpNamespace('Schema');
        $class = $namespace->addClass('Foo');

        self::assertEquals(
            sprintf('%s/Schema/Foo.php', $this->outputDir->url()),
            $resolver->resolve($configuration, $namespace, $class)
        );
    }

    public function testItCreatesSubdirectory(): void
    {
        $resolver = new FileNameResolver();

        $configuration = new Configuration([], $this->outputDir->url(), '');

        $namespace = new PhpNamespace('Schema');
        $class = $namespace->addClass('Foo');

        $resolver->resolve($configuration, $namespace, $class);

        self::assertInstanceOf(vfsStreamDirectory::class, $this->outputDir->getChild('Schema'));
    }

    public function testItResolvesWithBaseNamespaceAndNestedSubNamespace(): void
    {
        $resolver = new FileNameResolver();

        $configuration = new Configuration([], $this->outputDir->url(), 'Base');
        $namespace = new PhpNamespace('Base\\Sub\\Nested');
        $class = $namespace->addClass('MyClass');

        $expectedPath = sprintf('%s/Sub/Nested/MyClass.php', $this->outputDir->url());
        $actualPath = $resolver->resolve($configuration, $namespace, $class);

        self::assertEquals($expectedPath, $actualPath);
        $subDirectory = $this->outputDir->getChild('Sub');
        self::assertInstanceOf(vfsStreamDirectory::class, $subDirectory);
        self::assertInstanceOf(vfsStreamDirectory::class, $subDirectory->getChild('Nested'));
    }
}

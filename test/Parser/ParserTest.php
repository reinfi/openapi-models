<?php

declare(strict_types=1);

namespace Parser;

use DG\BypassFinals;
use InvalidArgumentException;
use Mthole\OpenApiMerge\FileHandling\File;
use Mthole\OpenApiMerge\OpenApiMerge;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Parser\Parser;

class ParserTest extends TestCase
{
    private vfsStreamDirectory $inputRoot;

    protected function setUp(): void
    {
        BypassFinals::enable();

        $this->inputRoot = vfsStream::setup('input');
    }

    public function testItMergesDirectoriesAndFiles(): void
    {
        $openApiMerger = $this->createMock(OpenApiMerge::class);
        $openApiMerger
            ->expects($this->once())
            ->method('mergeFiles')
            ->with(self::isInstanceOf(File::class), self::countOf(3), false);

        mkdir($this->inputRoot->url() . '/sub');
        file_put_contents($this->inputRoot->url() . '/login.yml', file_get_contents(__DIR__ . '/../spec/login.yml'));
        file_put_contents(
            $this->inputRoot->url() . '/sub/carrier.yml',
            file_get_contents(__DIR__ . '/../spec/carrier.yml')
        );
        file_put_contents(
            $this->inputRoot->url() . '/sub/common.yml',
            file_get_contents(__DIR__ . '/../spec/common.yml')
        );
        file_put_contents($this->inputRoot->url() . '/sub/jobs.yml', file_get_contents(__DIR__ . '/../spec/jobs.yml'));

        $configuration = new Configuration([
            $this->inputRoot->url() . '/sub',
            $this->inputRoot->url() . '/login.yml',
        ], '', '');

        $parser = new Parser($openApiMerger);

        $parserResult = $parser->parse($configuration);

        self::assertCount(4, $parserResult->parsedFiles);
        self::assertContainsOnly(File::class, $parserResult->parsedFiles);
    }

    public function testItThrowsExceptionIfNoFilesFound(): void
    {
        self::expectException(InvalidArgumentException::class);

        $openApiMerger = $this->createMock(OpenApiMerge::class);
        $openApiMerger->expects($this->never())->method('mergeFiles');

        $configuration = new Configuration([], '', '');

        $parser = new Parser($openApiMerger);

        $parser->parse($configuration);
    }
}

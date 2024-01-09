<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Command;

use MichaelPetri\TypedInput\TypedInput;
use Mthole\OpenApiMerge\FileHandling\File;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\TraitType;
use PackageVersions\Versions;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Configuration\ConfigurationBuilder;
use Reinfi\OpenApiModels\Generator\ClassGenerator;
use Reinfi\OpenApiModels\Parser\Parser;
use Reinfi\OpenApiModels\Writer\ClassWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateCommand extends Command
{
    public function __construct(
        private readonly ConfigurationBuilder $configurationBuilder,
        private readonly Parser $parser,
        private readonly ClassGenerator $classGenerator,
        private readonly ClassWriter $classWriter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('generate');

        $this->addOption(
            'config',
            'c',
            InputOption::VALUE_REQUIRED,
            'configuration file to use',
            'openapi-models.php'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $typedInput = TypedInput::fromInput($input);

        $io = new SymfonyStyle($input, $output);

        $io->info(sprintf('OpenApi-Models - Version %s', Versions::getVersion(Versions::rootPackageName())));

        $configuration = $this->configurationBuilder->buildFromFile(
            $typedInput->getOption('config')->asNonEmptyString()
        );

        $this->outputConfiguration($io, $configuration);

        $parserResult = $this->parser->parse($configuration);

        $io->section('Found following spec files');
        $io->listing(array_map(
            static fn (File $file): string => $file->getAbsoluteFile(),
            $parserResult->parsedFiles
        ));

        $namespaces = $this->classGenerator->generate($parserResult->openApi, $configuration);

        $this->classWriter->write($configuration, $namespaces);

        foreach ($namespaces as $namespace) {
            $this->outputNamespace($io, $namespace);
        }

        $io->success('Finished');

        return self::SUCCESS;
    }

    private function outputConfiguration(SymfonyStyle $io, Configuration $configuration): void
    {
        $io->writeln('Configuration');
        $io->table(
            ['Option', 'Value'],
            [
                ['Paths', join(',', $configuration->paths)],
                ['Namespace', $configuration->namespace],
                ['Output path', $configuration->outputPath],
                ['Clear output directory', $configuration->clearOutputDirectory ? 'yes' : 'no'],
                ['Use DateTimeInterface for dates', $configuration->dateTimeAsObject ? 'yes' : 'no'],
            ]
        );
    }

    private function outputNamespace(SymfonyStyle $io, PhpNamespace $namespace): void
    {
        $io->section('Namespace ' . $namespace->getName());
        $io->listing(array_map($this->outputClassOrEnum(...), $namespace->getClasses()));
    }

    private function outputClassOrEnum(ClassType|EnumType|InterfaceType|TraitType $object): string
    {
        $name = $object->getName();

        if ($name === null) {
            return 'Anonymous class';
        }

        return match ($object::class) {
            ClassType::class => 'Class ' . $name,
            EnumType::class => 'Enum ' . $name,
            InterfaceType::class => 'Interface ' . $name,
            TraitType::class => 'Trait ' . $name,
        };
    }
}

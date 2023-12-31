<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Command;

use MichaelPetri\TypedInput\TypedInput;
use Reinfi\OpenApiModels\Configuration\ConfigurationBuilder;
use Reinfi\OpenApiModels\Generator\ClassGenerator;
use Reinfi\OpenApiModels\Parser\Parser;
use Reinfi\OpenApiModels\Writer\ClassWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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

        $configuration = $this->configurationBuilder->buildFromFile(
            $typedInput->getOption('config')->asNonEmptyString()
        );

        $openApiDefinition = $this->parser->parse($configuration);

        $namespaces = $this->classGenerator->generate($openApiDefinition, $configuration);

        $this->classWriter->write($configuration, $namespaces);

        foreach ($namespaces as $identifier => $namespace) {
            $output->writeln(
                sprintf(
                    'Wrote %u files to output path "%s" for %s',
                    count($namespace->getClasses()),
                    $configuration->outputPath,
                    $identifier
                )
            );
        }

        return self::SUCCESS;
    }
}

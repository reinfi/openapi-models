<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Command;

use MichaelPetri\TypedInput\TypedInput;
use Reinfi\OpenApiModels\Configuration\ConfigurationBuilder;
use Reinfi\OpenApiModels\Generator\ClassGenerator;
use Reinfi\OpenApiModels\OutputFormatter\FormatterFactory;
use Reinfi\OpenApiModels\Parser\Parser;
use Reinfi\OpenApiModels\Validate\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ValidateCommand extends Command
{
    public function __construct(
        private readonly FormatterFactory $formatterFactory,
        private readonly ConfigurationBuilder $configurationBuilder,
        private readonly Parser $parser,
        private readonly ClassGenerator $classGenerator,
        private readonly Validator $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('validate');

        $this->addOption(
            'config',
            'c',
            InputOption::VALUE_REQUIRED,
            'configuration file to use',
            'openapi-models.php'
        );

        $this->addOption(
            'output-format',
            'o',
            InputOption::VALUE_REQUIRED,
            'Change output style (default, junit)'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $typedInput = TypedInput::fromInput($input);

        $configuration = $this->configurationBuilder->buildFromFile(
            $typedInput->getOption('config')
                ->asNonEmptyString()
        );

        $parserResult = $this->parser->parse($configuration);

        $models = $this->classGenerator->generate($parserResult->openApi, $configuration);

        $validationResult = $this->validator->validate($configuration, $models);

        $outputFormatter = $this->formatterFactory->create(
            $typedInput->getOption('output-format')
                ->asNonEmptyStringOrNull()
        );

        return $outputFormatter->formatOutput($validationResult, new SymfonyStyle($input, $output));
    }
}

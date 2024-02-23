<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Command;

use MichaelPetri\TypedInput\TypedInput;
use PackageVersions\Versions;
use Reinfi\OpenApiModels\Configuration\ConfigurationBuilder;
use Reinfi\OpenApiModels\Generator\ClassGenerator;
use Reinfi\OpenApiModels\Parser\Parser;
use Reinfi\OpenApiModels\Validate\ValidationFile;
use Reinfi\OpenApiModels\Validate\ValidationFileResult;
use Reinfi\OpenApiModels\Validate\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ValidateCommand extends Command
{
    public function __construct(
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
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $typedInput = TypedInput::fromInput($input);

        $io = new SymfonyStyle($input, $output);

        $io->info(sprintf('OpenApi-Models - Version %s', Versions::getVersion(Versions::rootPackageName())));

        $configuration = $this->configurationBuilder->buildFromFile(
            $typedInput->getOption('config')
                ->asNonEmptyString()
        );

        $parserResult = $this->parser->parse($configuration);

        $namespaces = $this->classGenerator->generate($parserResult->openApi, $configuration);

        $validationResult = $this->validator->validate($configuration, $namespaces);

        if ($validationResult->isValid()) {
            $io->success('Validation successful');

            return self::SUCCESS;
        }

        $io->section('Validation Result');
        $io->table(
            ['Class', 'Message', 'Path'],
            array_map(
                static fn (ValidationFile $file): array => [
                    $file->className,
                    match ($file->validationResult) {
                        ValidationFileResult::Ok => 'Ok',
                        ValidationFileResult::NotExisting => 'File is missing',
                        ValidationFileResult::Differs => 'Content differs',
                    },
                    $file->filePath,
                ],
                $validationResult->getInvalidFiles()
            )
        );

        $io->error('Validation failed, see errors above.');

        return self::FAILURE;
    }
}

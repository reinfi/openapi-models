<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\OutputFormatter;

use PackageVersions\Versions;
use Reinfi\OpenApiModels\Validate\ValidationFile;
use Reinfi\OpenApiModels\Validate\ValidationFileResult;
use Reinfi\OpenApiModels\Validate\ValidationResult;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

class DefaultFormatter implements OutputFormatterInterface
{
    public function formatOutput(ValidationResult $result, SymfonyStyle $output): int
    {
        $output->info(sprintf('OpenApi-Models - Version %s', Versions::getVersion(Versions::rootPackageName())));

        if ($result->isValid()) {
            $output->success('Validation successful');

            return Command::SUCCESS;
        }

        $output->section('Validation Result');
        $output->table(
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
                $result->getInvalidFiles()
            )
        );

        $output->error('Validation failed, see errors above.');

        return Command::FAILURE;
    }
}

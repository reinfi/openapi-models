<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Configuration;

use InvalidArgumentException;

class ConfigurationBuilder
{
    public function buildFromFile(string $configurationFile): Configuration
    {
        if (! is_file($configurationFile)) {
            throw new InvalidArgumentException(sprintf('Configuration file %s does not exist', $configurationFile));
        }

        $configurationValues = require $configurationFile;

        if ($configurationValues instanceof Configuration) {
            $this->validate($configurationValues);

            return $configurationValues;
        }

        if (! is_array($configurationValues)) {
            throw new InvalidArgumentException(
                sprintf('Configuration file "%s" does not return an array', $configurationFile)
            );
        }

        $configuration = new Configuration(
            $configurationValues['paths'] ?? [],
            $configurationValues['outputPath'] ?? '',
            $configurationValues['namespace'] ?? '',
            (bool) ($configurationValues['clearOutputDirectory'] ?? false),
        );

        $this->validate($configuration);

        return $configuration;
    }

    private function validate(Configuration $configuration): void
    {
        if (count($configuration->paths) === 0) {
            throw new InvalidArgumentException('There are no paths defined in your configuration');
        }

        if (! is_dir($configuration->outputPath)) {
            throw new InvalidArgumentException(
                sprintf('Output path "%s" is not a directory', $configuration->outputPath)
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Configuration;

use DateTimeInterface;
use InvalidArgumentException;

readonly class ConfigurationBuilder
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
            throw new InvalidArgumentException('Configuration must be an array');
        }

        // Assign and validate paths
        $paths = $configurationValues['paths'] ?? [];
        if (! is_array($paths)) {
            throw new InvalidArgumentException('Paths must be an array');
        }
        $paths = array_filter($paths, fn ($path) => is_string($path));
        if (count($paths) !== count($paths)) {
            throw new InvalidArgumentException('All paths must be strings');
        }

        // Assign and validate outputPath
        $outputPath = $configurationValues['outputPath'] ?? '';
        if (! is_string($outputPath)) {
            throw new InvalidArgumentException('Output path must be a string');
        }

        // Assign and validate namespace
        $namespace = $configurationValues['namespace'] ?? '';
        if (! is_string($namespace)) {
            throw new InvalidArgumentException('Namespace must be a string');
        }

        // Assign and validate clearOutputDirectory
        $clearOutputDirectory = (bool) ($configurationValues['clearOutputDirectory'] ?? false);

        // Assign and validate dateTimeAsObject
        $dateTimeAsObject = (bool) ($configurationValues['dateTimeAsObject'] ?? false);

        // Assign and validate dateFormat
        $dateFormat = $configurationValues['dateFormat'] ?? 'Y-m-d';
        if (! is_string($dateFormat)) {
            throw new InvalidArgumentException('Date format must be a string');
        }

        // Assign and validate dateTimeFormat
        $dateTimeFormat = $configurationValues['dateTimeFormat'] ?? DateTimeInterface::RFC3339;
        if (! is_string($dateTimeFormat)) {
            throw new InvalidArgumentException('Date time format must be a string');
        }

        $configuration = new Configuration(
            $paths,
            $outputPath,
            $namespace,
            $clearOutputDirectory,
            $dateTimeAsObject,
            $dateFormat,
            $dateTimeFormat,
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

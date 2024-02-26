<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\OutputFormatter;

use Reinfi\OpenApiModels\Validate\ValidationFileResult;
use Reinfi\OpenApiModels\Validate\ValidationResult;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

class JUnitFormatter implements OutputFormatterInterface
{
    public function formatOutput(ValidationResult $result, SymfonyStyle $output): int
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';

        $fileCount = $result->countFiles();
        $missingFiles = $result->getInvalidFiles(ValidationFileResult::NotExisting);
        $differenceFiles = $result->getInvalidFiles(ValidationFileResult::Differs);

        $xml .= sprintf(
            '<testsuite failures="%d" name="openapi-models" tests="%d" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/junit-team/junit5/r5.5.1/platform-tests/src/test/resources/jenkins-junit.xsd">',
            count($missingFiles) + count($differenceFiles),
            $fileCount
        );

        if (count($missingFiles) > 0) {
            $xml .= '<testcase name="missing">';
            foreach ($missingFiles as $file) {
                $xml .= sprintf(
                    '<failure type="ERROR" message="%s at path %s" />',
                    $this->escape($file->className),
                    $this->escape($file->filePath)
                );
            }
            $xml .= '</testcase>';
        }

        if (count($differenceFiles) > 0) {
            $xml .= '<testcase name="differs">';
            foreach ($differenceFiles as $file) {
                $xml .= sprintf(
                    '<failure type="ERROR" message="%s at path %s" />',
                    $this->escape($file->className),
                    $this->escape($file->filePath)
                );
            }
            $xml .= '</testcase>';
        }

        $xml .= '</testsuite>';

        $output->write($xml);

        return $result->isValid() ? Command::SUCCESS : Command::FAILURE;
    }

    private function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}

<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Validate;

class ValidationResult
{
    /**
     * @var ValidationFile[]
     */
    private array $files = [];

    public function add(ValidationFile $file): void
    {
        $this->files[] = $file;
    }

    public function isValid(): bool
    {
        foreach ($this->files as $file) {
            if (! $file->validationResult->isValid()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return ValidationFile[]
     */
    public function getInvalidFiles(): array
    {
        return array_filter(
            $this->files,
            static fn (ValidationFile $file): bool => ! $file->validationResult->isValid()
        );
    }
}

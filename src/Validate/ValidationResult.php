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

    public function countFiles(): int
    {
        return count($this->files);
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
    public function getInvalidFiles(?ValidationFileResult $filter = null): array
    {
        if ($filter === null) {
            return array_filter(
                $this->files,
                static fn (ValidationFile $file): bool => ! $file->validationResult->isValid()
            );
        }

        if ($filter === ValidationFileResult::Ok) {
            return [];
        }

        return array_filter(
            $this->files,
            static fn (ValidationFile $file): bool => $file->validationResult === $filter
        );
    }
}

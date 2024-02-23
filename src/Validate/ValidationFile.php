<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Validate;

class ValidationFile
{
    public function __construct(
        public string $className,
        public string $filePath,
        public ValidationFileResult $validationResult,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Model;

readonly class OneOfType
{
    public function __construct(
        /** @var array<string | ArrayType> $types */
        public array $types,
    ) {
    }

    public function containsType(string $type): bool
    {
        return in_array($type, $this->types, true);
    }

    public function nativeType(): string
    {
        return implode(
            '|',
            array_unique(
                array_map(
                    static fn (string|ArrayType $type): string => $type instanceof ArrayType ? 'array' : $type,
                    $this->types
                )
            )
        );
    }

    public function requiresPhpDoc(): bool
    {
        foreach ($this->types as $type) {
            if ($type instanceof ArrayType) {
                return true;
            }
        }

        return false;
    }

    public function phpDocType(): string
    {
        return implode(
            '|',
            array_map(
                static fn (string|ArrayType $type): string => $type instanceof ArrayType ? $type->docType : $type,
                $this->types
            )
        );
    }

    public function removeType(string $removedType): self
    {
        return new self(
            array_filter($this->types, static fn (string|ArrayType $type): bool => $type !== $removedType)
        );
    }
}

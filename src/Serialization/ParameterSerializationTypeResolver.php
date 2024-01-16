<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Serialization;

use DateTimeInterface;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Parameter;
use Reinfi\OpenApiModels\Model\ParameterSerializationType;

class ParameterSerializationTypeResolver
{
    /**
     * @return ParameterSerializationType[]
     */
    public function resolve(Method $constructor): array
    {
        return array_map(
            fn (Parameter $parameter): ParameterSerializationType => new ParameterSerializationType(
                $this->resolveType($parameter),
                $parameter,
                ! $parameter->hasDefaultValue()
            ),
            $constructor->getParameters()
        );
    }

    private function resolveType(Parameter $parameter): SerializableType
    {
        if ($this->isDateTime($parameter)) {
            return SerializableType::DateTime;
        }

        return SerializableType::None;
    }

    private function isDateTime(Parameter $parameter): bool
    {
        $type = $parameter->getType(true);

        if ($type === null) {
            return false;
        }

        if ($type->allows(DateTimeInterface::class)) {
            return true;
        }

        if ($type->getSingleName() === 'array' && $parameter->getComment() !== null) {
            if (str_contains($parameter->getComment(), DateTimeInterface::class)) {
                return true;
            }
        }

        return false;
    }
}

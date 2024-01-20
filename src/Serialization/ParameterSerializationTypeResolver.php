<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Serialization;

use DateTimeInterface;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PromotedParameter;
use Reinfi\OpenApiModels\Model\ParameterSerializationType;

class ParameterSerializationTypeResolver
{
    /**
     * @return ParameterSerializationType[]
     */
    public function resolve(ClassType $classType, Method $constructor): array
    {
        return array_map(
            fn (Parameter $parameter): ParameterSerializationType => new ParameterSerializationType(
                $this->resolveType($constructor, $parameter, $classType->hasProperty($parameter->getName())),
                $parameter,
                ! $parameter->hasDefaultValue()
            ),
            $constructor->getParameters()
        );
    }

    private function resolveType(Method $constructor, Parameter $parameter, bool $hasProperty): SerializableType
    {
        if ($this->isDateTime($parameter)) {
            return SerializableType::DateTime;
        }

        if ($parameter instanceof PromotedParameter) {
            return SerializableType::None;
        }

        if ($constructor->isVariadic() && $hasProperty) {
            return SerializableType::Dictionary;
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

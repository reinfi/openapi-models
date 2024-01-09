<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use DateTimeInterface;
use JsonSerializable;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpNamespace;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Exception\InvalidDateFormatException;
use Reinfi\OpenApiModels\Exception\PropertyNotFoundException;

readonly class SerializableResolver
{
    public function __construct(
        private TypeResolver $typeResolver
    ) {
    }

    public function needsSerialization(ClassType $class): bool
    {
        foreach ($class->getMethods() as $method) {
            foreach ($method->getParameters() as $parameter) {
                if ($this->needsParameterSerialization($parameter)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function addSerialization(
        Configuration $configuration,
        OpenApi $openApi,
        Schema $schema,
        PhpNamespace $namespace,
        ClassType $class,
        Method $constructor
    ): void {
        $promotedParameters = array_filter(
            $constructor->getParameters(),
            fn (Parameter $parameter): bool => $this->needsParameterSerialization($parameter),
        );

        if (count($promotedParameters) === 0) {
            return;
        }

        $namespace->addUse(JsonSerializable::class);
        $class->setImplements([JsonSerializable::class]);

        $method = $class->addMethod('jsonSerialize')->setReturnType('array');

        $method->addBody('return array_merge(get_object_vars($this), [');
        foreach ($promotedParameters as $parameter) {
            $property = $schema->properties[$parameter->getName()] ?? null;

            if (! $property instanceof Schema) {
                throw new PropertyNotFoundException($parameter->getName());
            }

            $type = $this->typeResolver->resolve($openApi, $property);

            switch ($type) {
                case Types::Array :
                    $arrayMapFunction = sprintf(
                        'array_map(static fn (%2$s $date): string => $date->format(\'%3$s\'), $this->%1$s)',
                        $parameter->getName(),
                        DateTimeInterface::class,
                        $this->resolveFormat($configuration, $openApi, $property, $type, $parameter)
                    );
                    if ($parameter->isNullable()) {
                        $method->addBody(sprintf(
                            '    \'%1$s\' => $this->%1$s === null ? $this->%1$s : %2$s,',
                            $parameter->getName(),
                            $arrayMapFunction
                        ));
                    } else {
                        $method->addBody(sprintf('    \'%1$s\' => %2$s,', $parameter->getName(), $arrayMapFunction));
                    }
                    break;
                case Types::OneOf :
                    $method->addBody(sprintf(
                        '    \'%1$s\' => $this->%1$s instanceOf %2$s ? $this->%1$s->format(\'%3$s\') : $this->%1$s,',
                        $parameter->getName(),
                        DateTimeInterface::class,
                        $this->resolveFormat($configuration, $openApi, $property, $type, $parameter)
                    ));
                    break;
                default :
                    $method->addBody(sprintf(
                        '    \'%1$s\' => $this->%1$s%2$s->format(\'%3$s\'),',
                        $parameter->getName(),
                        $parameter->isNullable() ? '?' : '',
                        $this->resolveFormat($configuration, $openApi, $property, $type, $parameter)
                    ));
            }
        }
        $method->addBody(']);');
    }

    private function needsParameterSerialization(Parameter $parameter): bool
    {
        $type = $parameter->getType(true);
        if ($type?->allows(DateTimeInterface::class)) {
            return true;
        }

        if ($type?->getSingleName() === 'array' && $parameter->getComment() !== null) {
            if (str_contains($parameter->getComment(), DateTimeInterface::class)) {
                return true;
            }
        }

        return false;
    }

    private function resolveFormat(
        Configuration $configuration,
        OpenApi $openApi,
        Schema $schema,
        Types|string $type,
        Parameter $parameter
    ): string {
        if ($type === Types::OneOf) {
            foreach ($schema->oneOf as $oneOfSchema) {
                $type = $this->typeResolver->resolve($openApi, $oneOfSchema);
                if (in_array($type, [Types::DateTime, Types::Date], true)) {
                    break;
                }
            }
        }

        if ($type === Types::Array && $schema->items !== null) {
            $type = $this->typeResolver->resolve($openApi, $schema->items);
        }

        return match ($type) {
            Types::Date => $configuration->dateFormat,
            Types::DateTime => $configuration->dateTimeFormat,
            default => throw new InvalidDateFormatException($parameter->getName())
        };
    }
}

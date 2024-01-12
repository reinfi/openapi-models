<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use DateTimeInterface;
use IteratorAggregate;
use JsonSerializable;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Property;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Exception\InvalidDateFormatException;
use Reinfi\OpenApiModels\Exception\PropertyNotFoundException;

readonly class SerializableResolver
{
    public function __construct(
        private TypeResolver $typeResolver,
        private ReferenceResolver $referenceResolver,
    ) {
    }

    public function needsSerialization(ClassType $class): SerializableType
    {
        $isArrayObject = in_array(IteratorAggregate::class, $class->getImplements(), true);

        foreach ($class->getMethods() as $method) {
            foreach ($method->getParameters() as $parameter) {
                if ($this->needsParameterSerialization($parameter)) {
                    return $isArrayObject ? SerializableType::ArrayObjectDateTime : SerializableType::DateTime;
                }
            }
        }

        foreach ($class->getProperties() as $property) {
            if ($this->needsParameterSerialization($property)) {
                return $isArrayObject ? SerializableType::ArrayObjectDateTime : SerializableType::DateTime;
            }
        }

        if ($isArrayObject) {
            return SerializableType::ArrayObject;
        }

        return SerializableType::None;
    }

    public function addSerialization(
        SerializableType $serializableType,
        Configuration $configuration,
        OpenApi $openApi,
        Schema $schema,
        PhpNamespace $namespace,
        ClassType $class,
        Method $constructor
    ): void {
        if ($serializableType === SerializableType::None) {
            return;
        }

        if ($serializableType === SerializableType::ArrayObject || $serializableType === SerializableType::ArrayObjectDateTime) {
            $namespace->addUse(JsonSerializable::class);
            $class->setImplements([...$class->getImplements(), JsonSerializable::class]);

            $this->addArrayObject(
                $configuration,
                $openApi,
                $class,
                $constructor,
                $schema,
                $serializableType === SerializableType::ArrayObjectDateTime
            );
            return;
        }

        $promotedParameters = array_filter(
            $constructor->getParameters(),
            fn (Parameter $parameter): bool => $this->needsParameterSerialization($parameter),
        );

        if (count($promotedParameters) === 0) {
            return;
        }

        $namespace->addUse(JsonSerializable::class);
        $class->setImplements([...$class->getImplements(), JsonSerializable::class]);

        $method = $class->addMethod('jsonSerialize')
            ->setReturnType('array');

        $method->addBody('return array_merge(get_object_vars($this), [');
        foreach ($promotedParameters as $parameter) {
            $property = $this->findPropertySchema($openApi, $schema, $parameter->getName());

            if ($property === null) {
                throw new PropertyNotFoundException($parameter->getName());
            }

            if ($property instanceof Reference) {
                $property = $this->referenceResolver->resolve($openApi, $property)
->schema;
            }

            $type = $this->typeResolver->resolve($openApi, $property);

            switch ($type) {
                case Types::Array:
                    $arrayMapFunction = sprintf(
                        'array_map(static fn (%2$s $date): string => $date->format(\'%3$s\'), $this->%1$s)',
                        $parameter->getName(),
                        DateTimeInterface::class,
                        $this->resolveFormat($configuration, $openApi, $property, $type, $parameter->getName())
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
                case Types::OneOf:
                    $method->addBody(sprintf(
                        '    \'%1$s\' => $this->%1$s instanceOf %2$s ? $this->%1$s->format(\'%3$s\') : $this->%1$s,',
                        $parameter->getName(),
                        DateTimeInterface::class,
                        $this->resolveFormat($configuration, $openApi, $property, $type, $parameter->getName())
                    ));
                    break;
                default:
                    $method->addBody(sprintf(
                        '    \'%1$s\' => $this->%1$s%2$s->format(\'%3$s\'),',
                        $parameter->getName(),
                        $parameter->isNullable() ? '?' : '',
                        $this->resolveFormat($configuration, $openApi, $property, $type, $parameter->getName())
                    ));
            }
        }
        $method->addBody(']);');
    }

    private function needsParameterSerialization(Parameter|Property $parameter): bool
    {
        if ($parameter->getType() === 'mixed') {
            return false;
        }

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

    private function findPropertySchema(OpenApi $openApi, Schema $schema, string $name): Schema|Reference|null
    {
        $property = $schema->properties[$name] ?? null;

        if ($property !== null) {
            return $property;
        }

        if (is_array($schema->allOf) && count($schema->allOf) > 0) {
            foreach ($schema->allOf as $allOfSchemaOrReference) {
                if ($allOfSchemaOrReference instanceof Reference) {
                    $allOfSchemaOrReference = $this->referenceResolver->resolve(
                        $openApi,
                        $allOfSchemaOrReference
                    )->schema;
                }

                $property = $this->findPropertySchema($openApi, $allOfSchemaOrReference, $name);

                if ($property !== null) {
                    return $property;
                }
            }
        }

        return null;
    }

    private function addArrayObject(
        Configuration $configuration,
        OpenApi $openApi,
        ClassType $class,
        Method $constructor,
        Schema $schema,
        bool $isDateTime
    ): void {
        $parameter = $constructor->getParameter('items');
        $method = $class->addMethod('jsonSerialize')
            ->setReturnType('array')
            ->setReturnNullable($parameter->isNullable());

        if (! $isDateTime || $schema->items === null) {
            $method->addBody('return $this->?;', [$parameter->getName()]);
            return;
        }

        $arrayMapFunction = sprintf(
            'array_map(static fn (%2$s $date): string => $date->format(\'%3$s\'), $this->%1$s)',
            $parameter->getName(),
            DateTimeInterface::class,
            $this->resolveFormat($configuration, $openApi, $schema, Types::Array, $parameter->getName())
        );
        if ($parameter->isNullable()) {
            $method->addBody(sprintf(
                'return $this->%1$s === null ? $this->%1$s : %2$s;',
                $parameter->getName(),
                $arrayMapFunction
            ));
        } else {
            $method->addBody(sprintf('return %s;', $arrayMapFunction));
        }
    }

    private function resolveFormat(
        Configuration $configuration,
        OpenApi $openApi,
        Schema $schema,
        Types|string $type,
        string $parameterName
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
            default => throw new InvalidDateFormatException($parameterName)
        };
    }
}

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
use Nette\PhpGenerator\PromotedParameter;
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
                $type = $parameter->getType(true);
                if ($type?->allows(DateTimeInterface::class)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function addSerialization(
        OpenApi $openApi,
        Schema $schema,
        PhpNamespace $namespace,
        ClassType $class,
        Method $constructor
    ): void {
        $promotedParameters = array_filter(
            $constructor->getParameters(),
            static fn (Parameter $parameter): bool => $parameter instanceof PromotedParameter && $parameter->getType(
                true
            )?->allows(DateTimeInterface::class),
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
                case Types::OneOf :
                    $method->addBody(sprintf(
                        '    \'%1$s\' => $this->%1$s instanceOf %2$s ? $this->%1$s->format(\'%3$s\') : $this->%1$s,',
                        $parameter->getName(),
                        DateTimeInterface::class,
                        $this->resolveFormat($openApi, $property, $type, $parameter)
                    ));
                    break;
                default :
                    $method->addBody(sprintf(
                        '    \'%1$s\' => $this->%1$s%2$s->format(\'%3$s\'),',
                        $parameter->getName(),
                        $parameter->isNullable() ? '?' : '',
                        $this->resolveFormat($openApi, $property, $type, $parameter)
                    ));
            }
        }
        $method->addBody(']);');
    }

    private function resolveFormat(OpenApi $openApi, Schema $schema, Types|string $type, Parameter $parameter): string
    {
        if ($type === Types::OneOf) {
            foreach ($schema->oneOf as $oneOfSchema) {
                $type = $this->typeResolver->resolve($openApi, $oneOfSchema);
                if (in_array($type, [Types::DateTime, Types::Date], true)) {
                    break;
                }
            }
        }

        return match ($type) {
            Types::Date => 'Y-m-d',
            Types::DateTime => DateTimeInterface::RFC3339,
            default => throw new InvalidDateFormatException($parameter->getName())
        };
    }
}

<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Serialization;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use DateTimeInterface;
use JsonSerializable;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Generator\Types;

class ArrayObjectSerialization
{
    public function __construct(
        private readonly DateTimeFormatResolver $dateTimeFormatResolver
    ) {
    }

    public function apply(
        Configuration $configuration,
        PhpNamespace $namespace,
        ClassType $class,
        OpenApi $openApi,
        Schema $schema
    ): void {
        $namespace->addUse(JsonSerializable::class);
        $class->setImplements([...$class->getImplements(), JsonSerializable::class]);

        $constructor = $class->getMethod('__construct');

        $this->addArrayObject(
            $configuration,
            $openApi,
            $class,
            $constructor,
            $schema,
            $this->isDateTimeArrayObject($constructor),
        );
    }

    private function isDateTimeArrayObject(Method $constructor): bool
    {
        $parameter = $constructor->getParameter('items');
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
            $this->dateTimeFormatResolver->resolveFormat(
                $configuration,
                $openApi,
                $schema,
                Types::Array,
                $parameter->getName()
            )
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
}

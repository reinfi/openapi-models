<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Serialization;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Schema;
use IteratorAggregate;
use JsonSerializable;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Reinfi\OpenApiModels\Configuration\Configuration;
use Reinfi\OpenApiModels\Model\ParameterSerializationType;

readonly class SerializableResolver
{
    public function __construct(
        private ParameterSerializationTypeResolver $parameterSerializationTypeResolver,
        private ArrayObjectSerialization $arrayObjectSerialization,
        private DateTimeSerializationResolver $dateTimeSerializationResolver,
    ) {
    }

    public function resolve(
        Configuration $configuration,
        OpenApi $openApi,
        Schema $schema,
        PhpNamespace $namespace,
        ClassType $class,
        Method $constructor
    ): void {
        $isArrayObject = in_array(IteratorAggregate::class, $class->getImplements(), true);

        if ($isArrayObject) {
            $this->arrayObjectSerialization->apply($configuration, $namespace, $class, $openApi, $schema);
            return;
        }

        $parameters = $this->parameterSerializationTypeResolver->resolve($constructor);

        $notRequiredParameters = array_filter(
            $parameters,
            static fn (ParameterSerializationType $parameterSerializationType): bool => ! $parameterSerializationType->required
        );
        $dateTimeParameters = array_filter(
            $parameters,
            static fn (ParameterSerializationType $parameterSerializationType): bool => $parameterSerializationType->type === SerializableType::DateTime
        );

        $hasNotRequiredParameter = count($notRequiredParameters) > 0;
        $hasDateTimeParameter = count($dateTimeParameters) > 0;
        if (! $hasDateTimeParameter && ! $hasNotRequiredParameter) {
            return;
        }

        $namespace->addUse(JsonSerializable::class);
        $class->setImplements([...$class->getImplements(), JsonSerializable::class]);

        $method = $class->addMethod('jsonSerialize')
            ->setReturnType('array');

        $dateTimeCodeParts = null;
        if ($hasDateTimeParameter) {
            $dateTimeCodeParts = $this->dateTimeSerializationResolver->resolve(
                $configuration,
                $openApi,
                $schema,
                $dateTimeParameters,
                ! $hasNotRequiredParameter
            );

            if (! $hasNotRequiredParameter) {
                array_map(static fn (string $code) => $method->addBody($code), $dateTimeCodeParts);

                return;
            }
        }

        $method->addBody('return array_filter(');

        if (is_array($dateTimeCodeParts)) {
            array_map(static fn (string $code) => $method->addBody(sprintf('    %s', $code)), $dateTimeCodeParts);
        } else {
            $method->addBody('    get_object_vars($this),');
        }

        $notRequiredParameterNames = array_map(
            static fn (ParameterSerializationType $serializationType): string => $serializationType->parameter->getName(),
            $notRequiredParameters
        );

        $method->addBody(
            '    static fn (mixed $value, string $key): bool => !(in_array($key, [...?], true) && $value === null),',
            [$notRequiredParameterNames]
        )->addBody('    ARRAY_FILTER_USE_BOTH')
            ->addBody(');');
    }
}

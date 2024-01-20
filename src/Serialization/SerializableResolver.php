<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Serialization;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Reference;
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
        private DictionarySerializationResolver $dictionarySerializationResolver,
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

        $parameters = $this->parameterSerializationTypeResolver->resolve($class, $constructor);

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
        $isDictionary = ($schema->additionalProperties instanceof Reference || $schema->additionalProperties instanceof Schema);
        if (! $hasDateTimeParameter && ! $hasNotRequiredParameter && ! $isDictionary) {
            return;
        }

        $namespace->addUse(JsonSerializable::class);
        $class->setImplements([...$class->getImplements(), JsonSerializable::class]);

        $method = $class->addMethod('jsonSerialize')
            ->setReturnType('array');

        /** @var string[] $parameterSerializeCodeParts */
        $parameterSerializeCodeParts = [];

        foreach ($parameters as $parameter) {
            if ($parameter->type === SerializableType::Dictionary) {
                array_push(
                    $parameterSerializeCodeParts,
                    ...$this->dictionarySerializationResolver->resolve($namespace, $parameter)
                );
                continue;
            }

            $parameterSerializeCodeParts[] = match ($parameter->type) {
                SerializableType::None => sprintf('\'%1$s\' => $this->%1$s,', $parameter->parameter->getName()),
                SerializableType::DateTime => $this->dateTimeSerializationResolver->resolve(
                    $configuration,
                    $openApi,
                    $schema,
                    $parameter
                ),
            };
        }

        if (! $hasNotRequiredParameter) {
            $method->addBody('return [');
            array_map(fn (string $code) => $method->addBody($this->intend($code)), $parameterSerializeCodeParts);
            $method->addBody('];');
            return;
        }

        $method->addBody('return array_filter(');
        $method->addBody($this->intend('['));

        array_map(fn (string $code) => $method->addBody($this->intend($code, 2)), $parameterSerializeCodeParts);

        $notRequiredParameterNames = array_map(
            static fn (ParameterSerializationType $serializationType): string => $serializationType->parameter->getName(),
            $notRequiredParameters
        );

        $method->addBody($this->intend('],'));

        $method->addBody(
            $this->intend(
                'static fn (mixed $value, string $key): bool => !(in_array($key, [...?], true) && $value === null),'
            ),
            [$notRequiredParameterNames]
        )->addBody($this->intend('ARRAY_FILTER_USE_BOTH'))
            ->addBody(');');
    }

    private function intend(string $code, int $intends = 1): string
    {
        return sprintf('%s%s', join('', array_fill(0, $intends, '    ')), $code);
    }
}

<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Serialization;

use IteratorAggregate;
use JsonSerializable;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use openapiphp\openapi\spec\OpenApi;
use openapiphp\openapi\spec\Reference;
use openapiphp\openapi\spec\Schema;
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
            ->addComment('@return array<string, mixed>')
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

        if (count($notRequiredParameterNames) === 1) {
            $method->addBody($this->intend('static fn (mixed $value): bool => $value === null'));
            $method->addBody(');');
            return;
        }

        $method->addBody($this->intend('static fn (mixed $value, string $key): bool => !(in_array($key, ['));

        array_map(fn (string $name) => $method->addBody($this->intend(sprintf('\'%1$s\',', $name), 2)), $notRequiredParameterNames);

        $method->addBody($this->intend('], true) && $value === null),'));
        $method->addBody($this->intend('ARRAY_FILTER_USE_BOTH'));
        $method->addBody(');');
    }

    private function intend(string $code, int $intends = 1): string
    {
        return sprintf('%s%s', join('', array_fill(0, $intends, '    ')), $code);
    }
}

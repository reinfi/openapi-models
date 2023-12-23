<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PromotedParameter;

readonly class PropertyTransformer
{
    public function __construct(
        private TypeTransformer $typeTransformer
    ) {
    }

    public function transform(
        OpenApi $openApi,
        PhpNamespace $namespace,
        Method $constructor,
        string $name,
        Schema|Reference $schema,
        bool $required
    ): PromotedParameter {
        $property = $constructor
            ->addPromotedParameter($name)
            ->setType($this->typeTransformer->transform($openApi, $schema, $namespace));

        if ($schema->nullable ?? false) {
            $property->setNullable();
        }

        if (! $required) {
            $property->setDefaultValue(null)->setNullable();
        }

        return $property;
    }
}

<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use Countable;
use IteratorAggregate;
use Nette\PhpGenerator\ClassLike;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Parameter;
use Reinfi\OpenApiModels\Model\ArrayType;
use Reinfi\OpenApiModels\Model\Imports;
use Traversable;

class ArrayObjectResolver
{
    public function resolve(ClassType $class, Method $constructor, ArrayType $arrayType, Imports $imports): void
    {
        $class->setImplements([IteratorAggregate::class, Countable::class, ArrayAccess::class]);
        $imports->addImport(...$class->getImplements());

        $parameter = $this->resolvePropertyAndParameter($class, $constructor, $arrayType);

        if ($arrayType->type instanceof ClassReference) {
            $imports->addImport($arrayType->type->name);
        }

        $this->addIterator($class, $parameter, $imports)->addCount($class, $parameter)->addOffsetExists(
            $class,
            $parameter
        )->addOffsetGet(
            $class,
            $parameter,
            $arrayType
        )->addOffsetSet($class, $imports)->addOffsetUnset($class, $imports);
    }

    private function resolvePropertyAndParameter(ClassType $class, Method $constructor, ArrayType $arrayType): Parameter
    {
        if ($arrayType->nullable) {
            return $constructor->addPromotedParameter('items')->setType('array')->setNullable()->setVisibility(
                ClassLike::VisibilityPrivate
            )->addComment($arrayType->docType);
        }

        $constructor->setVariadic();
        $parameter = $constructor->addParameter('items');

        if ($arrayType->type instanceof ClassReference) {
            $parameter->setType($arrayType->type->name);
        } else {
            $parameter->setType($arrayType->type);
        }

        $class->addProperty($parameter->getName())->setType('array')->setVisibility(
            ClassLike::VisibilityPrivate
        )->addComment($arrayType->docType);

        $constructor->addBody('$this->? = $?;', [$parameter->getName(), $parameter->getName()]);

        return $parameter;
    }

    private function addIterator(ClassType $class, Parameter $parameter, Imports $imports): self
    {
        $imports->addImport(Traversable::class, ArrayIterator::class);
        $method = $class->addMethod('getIterator')->setReturnType(Traversable::class);

        if ($parameter->isNullable()) {
            $method->addBody(
                sprintf('return new %s($this->%s ?? []);', ArrayIterator::class, $parameter->getName())
            );
        } else {
            $method->addBody(sprintf('return new %s($this->%s);', ArrayIterator::class, $parameter->getName()));
        }

        return $this;
    }

    private function addCount(ClassType $class, Parameter $parameter): self
    {
        $method = $class->addMethod('count')->setReturnType('int');

        if ($parameter->isNullable()) {
            $method->addBody(sprintf('return count($this->%s ?? []);', $parameter->getName()));
        } else {
            $method->addBody(sprintf('return count($this->%s);', $parameter->getName()));
        }

        return $this;
    }

    private function addOffsetExists(ClassType $class, Parameter $parameter): self
    {
        $method = $class->addMethod('offsetExists')->setReturnType('bool')->addBody(
            sprintf('return isset($this->%s[$offset]);', $parameter->getName())
        );

        $method->addParameter('offset')->setType('mixed');

        return $this;
    }

    private function addOffsetGet(ClassType $class, Parameter $parameter, ArrayType $arrayType): self
    {
        $method = $class->addMethod('offsetGet')->addBody(
            sprintf('return $this->%s[$offset] ?? null;', $parameter->getName())
        )->setReturnNullable();

        $method->addParameter('offset')->setType('mixed');

        if ($arrayType->type instanceof ClassReference) {
            $method->setReturnType($arrayType->type->name);
        } else {
            $method->setReturnType($arrayType->type);
        }

        return $this;
    }

    private function addOffsetSet(ClassType $class, Imports $imports): self
    {
        $method = $class->addMethod('offsetSet')->addBody(
            sprintf('throw new %s(\'Object is readOnly\');', BadMethodCallException::class)
        )->setReturnType('void');

        $method->addParameter('offset')->setType('mixed');
        $method->addParameter('value')->setType('mixed');

        $imports->addImport(BadMethodCallException::class);

        return $this;
    }

    private function addOffsetUnset(ClassType $class, Imports $imports): self
    {
        $method = $class->addMethod('offsetUnset')->addBody(
            sprintf('throw new %s(\'Object is readOnly\');', BadMethodCallException::class)
        )->setReturnType('void');

        $method->addParameter('offset')->setType('mixed');

        $imports->addImport(BadMethodCallException::class);

        return $this;
    }
}

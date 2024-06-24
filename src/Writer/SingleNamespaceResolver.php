<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Writer;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Property;
use Nette\PhpGenerator\TraitType;

class SingleNamespaceResolver
{
    public function resolve(PhpNamespace $namespace, ClassType|InterfaceType|EnumType|TraitType $class): PhpNamespace
    {
        $classOnlyNamespace = new PhpNamespace($namespace->getName());
        $classOnlyNamespace->add($class);

        foreach ($namespace->getUses() as $use) {
            if ($class instanceof ClassType && $use === $class->getExtends()) {
                $classOnlyNamespace->addUse($use);
            }

            if ($class instanceof ClassType && in_array($use, $class->getImplements(), true)) {
                $classOnlyNamespace->addUse($use);
            }

            foreach ($class->getMethods() as $method) {
                if ($method->getReturnType() !== 'mixed' && $method->getReturnType(true)?->allows($use)) {
                    $classOnlyNamespace->addUse($use);
                }

                foreach ($method->getParameters() as $parameter) {
                    $this->resolveUsagesForParameterOrProperty($classOnlyNamespace, $use, $parameter);
                }

                if (str_contains($method->getBody(), $use)) {
                    $classOnlyNamespace->addUse($use);
                    $method->setBody(str_replace($use, $namespace->simplifyName($use), $method->getBody()));
                }

                if ($method->getComment() !== null && str_contains($method->getComment(), $use)) {
                    $classOnlyNamespace->addUse($use);
                    $method->setComment(str_replace($use, $namespace->simplifyName($use), $method->getComment()));
                }

                if ($method->getReturnType(true)?->allows('array') && $method->getComment() !== null) {
                    if (str_contains($method->getComment(), $use)) {
                        $namespace->addUse($use);
                        $method->setComment(
                            str_replace($use, $namespace->simplifyName($use), $method->getComment())
                        );
                    }
                }
            }

            if ($class instanceof ClassType) {
                foreach ($class->getProperties() as $property) {
                    $this->resolveUsagesForParameterOrProperty($classOnlyNamespace, $use, $property);
                }
            }
        }

        return $classOnlyNamespace;
    }

    private function resolveUsagesForParameterOrProperty(
        PhpNamespace $namespace,
        string $use,
        Parameter|Property $parameterOrProperty
    ): void {
        if ($parameterOrProperty->getType() === 'mixed' || $parameterOrProperty->getType() === null) {
            return;
        }

        if ($parameterOrProperty->getType(true)?->allows($use) || $parameterOrProperty->getType() === $use) {
            $namespace->addUse($use);
        }

        if (in_array(
            'array',
            explode('|', $parameterOrProperty->getType())
        ) && $parameterOrProperty->getComment() !== null) {
            if (str_contains($parameterOrProperty->getComment(), $use)) {
                $namespace->addUse($use);
                $parameterOrProperty->setComment(
                    str_replace($use, $namespace->simplifyName($use), $parameterOrProperty->getComment())
                );
            }
        }
    }
}

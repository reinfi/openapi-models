<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use Nette\PhpGenerator\ClassLike;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class DictionaryResolver
{
    public function resolve(
        PhpNamespace $namespace,
        string $className,
        ClassType $class,
        string $dictionaryType
    ): void {
        $dictionaryClass = $this->resolveDictionaryClass($namespace, $className, $dictionaryType);
        $dictionaryClassName = $dictionaryClass->getName();

        assert($dictionaryClassName !== null);

        $constructor = $class->getMethod('__construct');
        $constructor->setVariadic();
        $constructor->addParameter('dictionaries')
            ->setType($namespace->resolveName($dictionaryClassName));
        $constructor->addBody('$this->dictionaries = $dictionaries;');

        $class->addProperty('dictionaries')
            ->setVisibility(ClassLike::VisibilityPrivate)->setType('array')->setComment(
                sprintf('@var %s[]', $namespace->resolveName($dictionaryClassName))
            );
    }

    private function resolveDictionaryClass(
        PhpNamespace $namespace,
        string $className,
        string $dictionaryType
    ): ClassType {
        $dictionaryClass = $namespace->addClass(sprintf('%sDictionary', $className))
            ->setReadOnly();

        $constructor = $dictionaryClass->addMethod('__construct');

        $constructor->addPromotedParameter('key')
            ->setType('string');
        $constructor->addPromotedParameter('value')
            ->setType($namespace->resolveName($dictionaryType));

        return $dictionaryClass;
    }
}

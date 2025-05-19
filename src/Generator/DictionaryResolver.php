<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Generator;

use Nette\PhpGenerator\ClassLike;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Reinfi\OpenApiModels\Model\ArrayType;
use Reinfi\OpenApiModels\Model\ClassModel;
use Reinfi\OpenApiModels\Model\Imports;
use Reinfi\OpenApiModels\Model\OneOfType;

class DictionaryResolver
{
    public function resolve(
        ClassModel $classModel,
        string $className,
        ClassType $class,
        string|ArrayType|OneOfType $dictionaryType,
    ): void {
        $namespace = $classModel->namespace;
        $dictionaryClassModel = $this->resolveDictionaryClass($namespace, $className, $dictionaryType);
        $dictionaryClassName = $dictionaryClassModel->className;

        $classModel->addInlineModel($dictionaryClassModel);

        $classModel->imports->addImport($dictionaryClassModel->namespace->resolveName($dictionaryClassName));

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
        string|ArrayType|OneOfType $dictionaryType
    ): ClassModel {
        $dictionaryClassName = sprintf('%sDictionary', $className);
        $dictionaryClass = $namespace->addClass($dictionaryClassName)
            ->setReadOnly();

        $constructor = $dictionaryClass->addMethod('__construct');

        $constructor->addPromotedParameter('key')
            ->setType('string');

        if ($dictionaryType instanceof ArrayType) {
            $constructor->addPromotedParameter('value')
                ->setType('array')
                ->setComment(sprintf('@var %s $%s', $dictionaryType->docType, 'value'));
        } elseif ($dictionaryType instanceof OneOfType) {
            $constructor->addPromotedParameter('value')
                ->setType($dictionaryType->nativeType())
                ->setComment($dictionaryType->phpDocType());
        } else {
            $constructor->addPromotedParameter('value')
                ->setType($dictionaryType);
        }

        return new ClassModel($dictionaryClassName, $namespace, $dictionaryClass, new Imports($namespace));
    }
}

<?php

declare(strict_types=1);

namespace Reinfi\OpenApiModels\Serialization;

use Nette\PhpGenerator\PhpNamespace;
use Reinfi\OpenApiModels\Exception\DictionarySerializeException;
use Reinfi\OpenApiModels\Model\ParameterSerializationType;

class DictionarySerializationResolver
{
    /**
     * @return string[]
     */
    public function resolve(PhpNamespace $namespace, ParameterSerializationType $parameter): array
    {
        $dictionaryProperty = $parameter->parameter->getName();
        $dictionaryType = $parameter->parameter->getType();

        if (! is_string($dictionaryType)) {
            throw new DictionarySerializeException();
        }

        $dictionaryClass = $namespace->getClasses()[$namespace->simplifyName($dictionaryType)] ?? null;

        if ($dictionaryClass === null) {
            throw new DictionarySerializeException();
        }

        $dictionaryClassConstructor = $dictionaryClass->getMethod('__construct');
        $valueParameter = $dictionaryClassConstructor->getParameter('value');
        $valueType = $valueParameter
            ->getType();

        if (! is_string($valueType)) {
            throw new DictionarySerializeException();
        }

        $inlineArrayReturnType = null;

        if ($valueType === 'array' && $valueParameter->getComment() !== null) {
            if (preg_match('/^@var (?<type>.*)\[]/', $valueParameter->getComment(), $matches)) {
                $inlineArrayReturnType = $this->intend(sprintf('/** @return %s[] */', $matches['type']));
            }
        }

        return array_filter([
            '...array_map(',
            $inlineArrayReturnType,
            $this->intend(
                sprintf('fn (int $index): %s => $this->%s[$index]->value,', $namespace->simplifyName(
                    $valueType
                ), $dictionaryProperty)
            ),
            $this->intend('array_flip('),
            $this->intend('array_map(', 2),
            $this->intend(
                sprintf(
                    'static fn (%s $dictionary): string => $dictionary->key,',
                    $namespace->simplifyName($dictionaryType)
                ),
                3
            ),
            $this->intend(sprintf('$this->%s', $dictionaryProperty), 3),
            $this->intend(')', 2),
            $this->intend(')'),
            ')',
        ]);
    }

    private function intend(string $code, int $intends = 1): string
    {
        return sprintf('%s%s', join('', array_fill(0, $intends, '    ')), $code);
    }
}

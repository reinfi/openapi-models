<?php

declare(strict_types=1);

use PHP_CodeSniffer\Standards\PSR12\Sniffs\Files\ImportStatementSniff;
use PhpCsFixer\Fixer\Import\FullyQualifiedStrictTypesFixer;
use PhpCsFixer\Fixer\Whitespace\TypeDeclarationSpacesFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $configurator): void {
    $configurator->parallel();

    $configurator->paths([__DIR__ . '/config/', __DIR__ . '/src/', __DIR__ . '/test/', __DIR__ . '/ecs.php']);

    $configurator->skip([__DIR__ . '/test/Acceptance/ExpectedClasses', __DIR__ . '/test/output']);

    // import SetList here in the end of ecs. is on purpose
    // to avoid overridden by existing Skip Option in current config
    $configurator->import(SetList::PSR_12);
    $configurator->import(SetList::COMMON);
    $configurator->import(SetList::NAMESPACES);
    $configurator->import(SetList::CLEAN_CODE);
    $configurator->import(SetList::SYMPLIFY);

    $configurator->ruleWithConfiguration(TypeDeclarationSpacesFixer::class, [
        'elements' => ['function', 'property'],
    ]);
    $configurator->rule(FullyQualifiedStrictTypesFixer::class);
    $configurator->rule(ImportStatementSniff::class);

    $configurator->lineEnding("\n");
};

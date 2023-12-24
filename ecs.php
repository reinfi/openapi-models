<?php

declare(strict_types=1);

use PHP_CodeSniffer\Standards\PSR12\Sniffs\Files\ImportStatementSniff;
use PhpCsFixer\Fixer\Import\FullyQualifiedStrictTypesFixer;
use PhpCsFixer\Fixer\Whitespace\TypeDeclarationSpacesFixer;
use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $configurator): void {
    $configurator->parallel();

    $configurator->paths([__DIR__ . '/config/', __DIR__ . '/src/', __DIR__ . '/ecs.php']);

    // import SetList here in the end of ecs. is on purpose
    // to avoid overridden by existing Skip Option in current config
    $configurator->import(SetList::PSR_12);
    $configurator->import(SetList::COMMON);
    $configurator->import(SetList::NAMESPACES);
    $configurator->import(SetList::CLEAN_CODE);

    $configurator->ruleWithConfiguration(TypeDeclarationSpacesFixer::class, [
        'elements' => ['function', 'property'],
    ]);
    $configurator->rule(FullyQualifiedStrictTypesFixer::class);
    $configurator->rule(ImportStatementSniff::class);
    $configurator->rule(LineLengthFixer::class);

    $configurator->lineEnding("\n");
};

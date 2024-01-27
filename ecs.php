<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([__DIR__ . '/config', __DIR__ . '/src', __DIR__ . '/test'])->withRootFiles()
    ->withSkip([__DIR__ . '/test/Acceptance/ExpectedClasses', __DIR__ . '/test/output'])
    ->withPreparedSets(psr12: true, common: true, symplify: true);

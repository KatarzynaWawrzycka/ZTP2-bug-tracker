<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Symfony\Set\TwigSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/assets',
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])

    ->withParallel()
    ->withPhpVersion(80500)

    // Auto-detect Symfony, Doctrine, Twig, etc. based on composer.lock
    ->withComposerBased(symfony: true)

    // PHP 8.5 rules
    ->withSets([
        LevelSetList::UP_TO_PHP_82,
    ])

    // Optional: Twig rules
    ->withSets([
        TwigSetList::TWIG_24,
    ])

    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0)

    // Control import behavior: disable importing short class names
    // Signature is: withImportNames(
    //   importShortClasses: bool = true,
    //   importDocBlockNames: bool = true,
    //   removeUnusedImports: bool = true
    // )
    ->withImportNames(importShortClasses: false);

<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        'app/',
        'Modules/*/app/',
        'database/',
        'routes/',
    ])
    ->withSkip([
        'vendor/',
        'storage/',
        'bootstrap/cache/',
    ])
    ->withSets([
        SetList::PHP_84,
        LaravelSetList::LARAVEL_120,
    ]);

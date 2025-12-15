<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPhpVersion(PhpVersion::PHP_84)
    ->withPaths([
        __DIR__ . '/class',
        __DIR__ . '/controller',
        __DIR__ . '/integration_tests',
        __DIR__ . '/lib',
        __DIR__ . '/model',
        __DIR__ . '/public',
        __DIR__ . '/public.ssl',
        __DIR__ . '/script',
        __DIR__ . '/src',
        __DIR__ . '/test',
        __DIR__ . '/tests',
    ])
    ->withRules([
        ExplicitNullableParamTypeRector::class,
    ])
    ;

<?php

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $config): void {
    $config->paths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);

    $config->bootstrapFiles([
        __DIR__.'/vendor/autoload.php',
        __DIR__.'/tools/rector/vendor/autoload.php',
    ]);

    $config->sets([
        LevelSetList::UP_TO_PHP_83,
    ]);
};

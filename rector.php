<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $containerConfigurator): void {
    $containerConfigurator->paths([__DIR__ . '/src', __DIR__ . '/tests']);

    $containerConfigurator->import(SetList::PHP_54);
    $containerConfigurator->import(SetList::PHP_55);
    $containerConfigurator->import(SetList::PHP_56);
    $containerConfigurator->import(SetList::PHP_70);
    $containerConfigurator->import(SetList::PHP_71);
    $containerConfigurator->import(SetList::PHP_72);
    $containerConfigurator->import(SetList::PHP_73);
    $containerConfigurator->import(SetList::PHP_74);

    $containerConfigurator->import(SetList::EARLY_RETURN);
    $containerConfigurator->import(SetList::TYPE_DECLARATION);
};

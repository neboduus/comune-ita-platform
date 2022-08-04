<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;

return static function (RectorConfig $rectorConfig): void
{
  $rectorConfig->symfonyContainerXml(__DIR__ . '/var/cache/dev/srcApp_KernelDevDebugContainer.xml');

  $rectorConfig->sets([
    //SetList::CODE_QUALITY,
    //SetList::CODING_STYLE,
    //SetList::FLYSYSTEM_20,
    //SetList::DEAD_CODE,
    //SetList::TYPE_DECLARATION_STRICT,
    SetList::PHP_74,
    SymfonySetList::SYMFONY_STRICT,
    SymfonySetList::SYMFONY_CODE_QUALITY,
    SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
    SymfonySetList::SYMFONY_44,
  ]);

  $rectorConfig->rule(TypedPropertyRector::class);
  $rectorConfig->phpVersion(PhpVersion::PHP_74);
  $rectorConfig->phpstanConfig(__DIR__ . '/phpstan.neon');
};

<?php

namespace App\DependencyInjection\Compiler;

use App\Services\SchedulableActionRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SchedulableActionPass implements CompilerPassInterface
{
  private $tagName;

  public function __construct($tagName = 'ocsdc.schedulable_action')
  {
    $this->tagName = $tagName;
  }

  public function process(ContainerBuilder $container)
  {
    if (!$container->has(SchedulableActionRegistry::class)) {
      return;
    }

    $definition = $container->findDefinition(SchedulableActionRegistry::class);
    $taggedServices = $container->findTaggedServiceIds($this->tagName);
    foreach ($taggedServices as $id => $tags) {
      foreach ($tags as $attributes) {
        if (isset($attributes['alias'])) {
          $definition->addMethodCall('registerService', [
            new Reference($id),
            $attributes['alias']
          ]);
          break;
        }
      }
    }
  }
}

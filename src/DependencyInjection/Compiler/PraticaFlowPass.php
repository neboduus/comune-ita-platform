<?php

namespace App\DependencyInjection\Compiler;

use App\Form\PraticaFlowRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PraticaFlowPass implements CompilerPassInterface
{
  private $tagName;

  public function __construct($tagName = 'ocsdc.pratica.flow')
  {
    $this->tagName = $tagName;
  }

  public function process(ContainerBuilder $container)
  {
    if (!$container->has(PraticaFlowRegistry::class)) {
      return;
    }

    $definition = $container->findDefinition(PraticaFlowRegistry::class);
    $taggedServices = $container->findTaggedServiceIds($this->tagName);

    foreach ($taggedServices as $id => $tags) {
      $alias = null;
      foreach ($tags as $attributes) {
        if (isset($attributes['alias']) && !$alias) {
          $alias = $attributes['alias'];
        }
      }
      $definition->addMethodCall('registerFlow', [new Reference($id), $alias]);
    }
  }
}

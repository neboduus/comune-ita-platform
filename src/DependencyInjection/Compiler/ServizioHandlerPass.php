<?php

namespace App\DependencyInjection\Compiler;

use App\Services\ServizioHandlerRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ServizioHandlerPass implements CompilerPassInterface
{
    private $tagName;

    public function __construct($tagName = 'app.servizio.handler')
    {
        $this->tagName = $tagName;
    }

    public function process(ContainerBuilder $container)
    {
        if (!$container->has(ServizioHandlerRegistry::class)) {
            return;
        }

        $definition = $container->findDefinition(ServizioHandlerRegistry::class);
        $taggedServices = $container->findTaggedServiceIds($this->tagName);
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                if (isset($attributes['alias'])) {
                    $definition->addMethodCall('registerHandler', [
                        new Reference($id),
                        $attributes['alias']
                    ]);
                    break;
                }
            }
        }
    }
}

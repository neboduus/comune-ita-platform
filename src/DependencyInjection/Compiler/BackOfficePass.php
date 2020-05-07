<?php

namespace App\DependencyInjection\Compiler;

use App\Services\BackOfficeCollection;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class BackOfficePass implements CompilerPassInterface
{
    private $tagName;

    public function __construct($tagName = 'app.backoffice')
    {
        $this->tagName = $tagName;
    }

    public function process(ContainerBuilder $container)
    {
        if (!$container->has(BackOfficeCollection::class)) {
            return;
        }

        $definition = $container->findDefinition(BackOfficeCollection::class);
        $taggedServices = $container->findTaggedServiceIds($this->tagName);

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addBackOffice', [new Reference($id)]);
        }
    }
}

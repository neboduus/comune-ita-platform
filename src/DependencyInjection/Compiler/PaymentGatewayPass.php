<?php

namespace App\DependencyInjection\Compiler;

use App\Payment\PaymentGatewayRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PaymentGatewayPass implements CompilerPassInterface
{
  private $tagName;

  public function __construct($tagName = 'ocsdc.payment_gateway')
  {
    $this->tagName = $tagName;
  }

  public function process(ContainerBuilder $container)
  {
    if (!$container->has(PaymentGatewayRegistry::class)) {
      return;
    }

    $definition = $container->findDefinition(PaymentGatewayRegistry::class);
    $taggedServices = $container->findTaggedServiceIds($this->tagName);
    foreach ($taggedServices as $id => $tags) {
      $definition->addMethodCall('registerPaymentGateway', [new Reference($id)]);
    }
  }
}

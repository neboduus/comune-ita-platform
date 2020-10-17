<?php

namespace App;

use App\Form\PraticaFlowRegistry;
use App\Handlers\Servizio\ServizioHandlerRegistry;
use App\Protocollo\ProtocolloHandlerRegistry;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

class InstanceKernel extends BaseKernel
{
  use MicroKernelTrait;

  const DEFAULT_PREFIX = 'sdc';

  private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

  /** @var string */
  protected $identifier;

  /** @var array */
  protected $instanceParameters;

  /** @return string */
  public function getIdentifier()
  {
    return $this->identifier;
  }

  /** @param string $identifier */
  public function setIdentifier($identifier)
  {
    $this->identifier = $identifier;
  }

  /** @return array */
  public function getInstanceParameters()
  {
    return $this->instanceParameters;
  }

  /** @param array $instanceParameters */
  public function setInstanceParameters(array $instanceParameters)
  {
    $this->instanceParameters = $instanceParameters;
  }

  public function registerBundles(): iterable
  {
    $contents = require $this->getProjectDir().'/config/bundles.php';
    foreach ($contents as $class => $envs) {
      if ($envs[$this->environment] ?? $envs['all'] ?? false) {
        yield new $class();
      }
    }
  }

  public function getProjectDir(): string
  {
    return \dirname(__DIR__);
  }

  public function getCacheDir()
  {
    return dirname(__DIR__).'/var/cache/'.$this->getIdentifier().'/'.$this->getEnvironment();
  }

  public function getLogDir()
  {
    return dirname(__DIR__).'/var/log/'.$this->getIdentifier().'/'.$this->getEnvironment();
  }

  protected function getContainerClass()
  {
    return parent::getContainerClass() . '_' . md5(json_encode($this->getInstanceParameters()));
  }

  protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
  {
    $container->addResource(new FileResource($this->getProjectDir().'/config/bundles.php'));
    $container->setParameter('container.dumper.inline_class_loader', \PHP_VERSION_ID < 70400 || $this->debug);
    $container->setParameter('container.dumper.inline_factories', true);
    $confDir = $this->getProjectDir().'/config';

    $loader->load($confDir.'/{packages}/*'.self::CONFIG_EXTS, 'glob');
    $loader->load($confDir.'/{packages}/'.$this->environment.'/*'.self::CONFIG_EXTS, 'glob');
    $loader->load($confDir.'/{services}'.self::CONFIG_EXTS, 'glob');
    $loader->load($confDir.'/{services}_'.$this->environment.self::CONFIG_EXTS, 'glob');

    $this->loadInstance($container, $loader);
    $this->loadRedistry($container);
  }

  protected function configureRoutes(RouteCollectionBuilder $routes): void
  {
    $confDir = $this->getProjectDir().'/config';

    $routes->import($confDir.'/{routes}/'.$this->environment.'/*'.self::CONFIG_EXTS, '/', 'glob');
    $routes->import($confDir.'/{routes}/*'.self::CONFIG_EXTS, '/', 'glob');
    $routes->import($confDir.'/{routes}'.self::CONFIG_EXTS, '/', 'glob');
  }


  protected function loadInstance(ContainerBuilder $container, LoaderInterface $loader)
  {
    $instanceParameters = $this->getInstanceParameters();
    $instanceParameters['instance'] = $this->getIdentifier();
    if (!isset($instanceParameters['prefix'])) {
      $instanceParameters['prefix'] = self::DEFAULT_PREFIX;
    }

    $loader->load(
      function (ContainerBuilder $container) use ($instanceParameters) {
        if (!\is_array($instanceParameters)) {
          throw new InvalidArgumentException('The "parameters" key should contain an array. Check your YAML syntax.');
        }
        foreach ($instanceParameters as $key => $value) {
          $container->setParameter($key, $value);
        }
      }
    );

  }

  protected function loadRedistry(ContainerBuilder $container)
  {

    if ($container->has(ServizioHandlerRegistry::class)) {
      $definition = $container->findDefinition(ServizioHandlerRegistry::class);
      $taggedServices = $container->findTaggedServiceIds('ocsdc.servizio.handler');
      foreach ($taggedServices as $id => $tags) {
        foreach ($tags as $attributes) {
          if (isset($attributes['alias'])) {
            $definition->addMethodCall(
              'registerHandler',
              [
                new Reference($id),
                $attributes['alias'],
              ]
            );
            break;
          }
        }
      }
    }

    if ($container->has(PraticaFlowRegistry::class)) {
      $definition = $container->findDefinition(PraticaFlowRegistry::class);
      $taggedServices = $container->findTaggedServiceIds('ocsdc.pratica.flow');
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

    if ($container->has(ProtocolloHandlerRegistry::class)) {
      $definition = $container->findDefinition(ProtocolloHandlerRegistry::class);
      $taggedServices = $container->findTaggedServiceIds('ocsdc.protocollo.handler');
      foreach ($taggedServices as $id => $tags) {
        $alias = null;
        foreach ($tags as $attributes) {
          if (isset($attributes['alias']) && !$alias) {
            $alias = $attributes['alias'];
          }
        }
        $definition->addMethodCall('registerHandler', [new Reference($id), $alias]);
      }
    }
  }
}

<?php

namespace App;

use App\BackOffice\BackOfficeInterface;
use App\DependencyInjection\Compiler\BackOfficePass;
use App\DependencyInjection\Compiler\PaymentGatewayPass;
use App\DependencyInjection\Compiler\PraticaFlowPass;
use App\DependencyInjection\Compiler\ProtocolloHandlerPass;
use App\DependencyInjection\Compiler\SchedulableActionPass;
use App\DependencyInjection\Compiler\ServizioHandlerPass;
use App\Payment\PaymentDataInterface;
use App\Protocollo\ProtocolloHandlerInterface;
use App\Utils\ConfigUtils;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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
  public function getInstanceParameters(): array
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

    if (!isset($instanceParameters['prefix']) || empty($instanceParameters['prefix'])) {
      $instanceParameters['prefix'] = self::DEFAULT_PREFIX;
    }

    $loader->load(
      function (ContainerBuilder $container) use ($instanceParameters) {
        if (!\is_array($instanceParameters)) {
          throw new InvalidArgumentException('The "parameters" key should contain an array. Check your YAML syntax.');
        }
        foreach ($instanceParameters as $key => $value) {
          if (is_array($value)) {
            $parameter = $container->getParameter($key);
            $container->setParameter($key, ConfigUtils::arrayMergeRecursiveDistinct($parameter, $value));
          } else {
            $container->setParameter($key, $value);
          }
        }
      }
    );
  }

  protected function build(ContainerBuilder $container)
  {
    $container->registerForAutoconfiguration(BackOfficeInterface::class)
      ->addTag('ocsdc.backoffice');
    $container->addCompilerPass(new BackOfficePass('ocsdc.backoffice'));

    $container->registerForAutoconfiguration(PaymentDataInterface::class)
      ->addTag('ocsdc.payment_gateway');
    $container->addCompilerPass(new PaymentGatewayPass('ocsdc.payment_gateway'));

    $container->registerForAutoconfiguration(ProtocolloHandlerInterface::class)
      ->addTag('app.protocollo.handler');
    $container->addCompilerPass(new ProtocolloHandlerPass('ocsdc.protocollo.handler'));

    $container->addCompilerPass(new PraticaFlowPass('ocsdc.pratica.flow'));

    $container->addCompilerPass(new ServizioHandlerPass('ocsdc.servizio.handler'));

    $container->addCompilerPass(new SchedulableActionPass('ocsdc.schedule_action_handler'));
  }
}

<?php

use AppBundle\Form\PraticaFlowRegistry;
use AppBundle\Handlers\Servizio\ServizioHandlerRegistry;
use AppBundle\Protocollo\ProtocolloHandlerRegistry;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel;


class InstanceKernel extends Kernel implements CompilerPassInterface
{
  const DEFAULT_PREFIX = 'sdc';

  /**
   * @var string
   */
  protected $identifier;

  /**
   * @var array
   */
  protected $instanceParameters;

  public function registerBundles()
  {
    $bundles = [
      new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
      new Symfony\Bundle\SecurityBundle\SecurityBundle(),
      new Symfony\Bundle\TwigBundle\TwigBundle(),
      new Symfony\Bundle\MonologBundle\MonologBundle(),
      new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
      new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
      new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
      new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
      new FOS\UserBundle\FOSUserBundle(),
      new Craue\FormFlowBundle\CraueFormFlowBundle(),
      new Vich\UploaderBundle\VichUploaderBundle(),
      new JMS\SerializerBundle\JMSSerializerBundle(),
      new Knp\Bundle\SnappyBundle\KnpSnappyBundle(),
      new EightPoints\Bundle\GuzzleBundle\GuzzleBundle(),
      new Symfony\WebpackEncoreBundle\WebpackEncoreBundle(),
      new Anyx\LoginGateBundle\LoginGateBundle(),
      new Xiidea\EasyAuditBundle\XiideaEasyAuditBundle(),
      new Omines\DataTablesBundle\DataTablesBundle(),
      new FOS\RestBundle\FOSRestBundle(),
      new Nelmio\ApiDocBundle\NelmioApiDocBundle(),
      new Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle(),
      new EWZ\Bundle\RecaptchaBundle\EWZRecaptchaBundle(),
      new Sentry\SentryBundle\SentryBundle(),
      new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
      new Flagception\Bundle\FlagceptionBundle\FlagceptionBundle(),
      new Artprima\PrometheusMetricsBundle\ArtprimaPrometheusMetricsBundle(),
      new Oneup\FlysystemBundle\OneupFlysystemBundle(),
      new WhiteOctober\BreadcrumbsBundle\WhiteOctoberBreadcrumbsBundle(),
      new AppBundle\AppBundle(),
    ];

    if ($this->debug) {
      $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
      $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
      $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
      $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
      $bundles[] = new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle();
    }

    return $bundles;
  }

  public function getCacheDir()
  {
    return dirname(__DIR__).'/var/cache/'.$this->getIdentifier().'/'.$this->getEnvironment();
  }

  /**
   * @return string
   */
  public function getIdentifier()
  {
    return $this->identifier;
  }

  /**
   * @param string $identifier
   */
  public function setIdentifier($identifier)
  {
    $this->identifier = $identifier;
  }

  public function getLogDir()
  {
    return dirname(__DIR__).'/var/logs/'.$this->getIdentifier().'/'.$this->getEnvironment();
  }

  protected function getContainerClass()
  {
    return parent::getContainerClass() . '_' . md5(json_encode($this->getInstanceParameters()));
  }

  public function registerContainerConfiguration(LoaderInterface $loader)
  {
    $loader->load($this->getRootDir().'/config/common/config_'.$this->getEnvironment().'.yml');

    $instanceParameters = $this->getInstanceParameters();
    $instanceParameters['instance'] = $this->getIdentifier();
    $instanceParameters['prefix'] = $this->getIdentifier();
    if (!isset($instanceParameters['prefix']) || empty($instanceParameters['prefix'])) {
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

  public function getRootDir()
  {
    return __DIR__;
  }

  /**
   * @return array
   */
  public function getInstanceParameters()
  {
    return $this->instanceParameters;
  }

  /**
   * @param array $instanceParameters
   */
  public function setInstanceParameters(array $instanceParameters)
  {
    $this->instanceParameters = $instanceParameters;
  }

  public function process(ContainerBuilder $container)
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

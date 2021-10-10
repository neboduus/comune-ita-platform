<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
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
      new AppBundle\AppBundle(),
    ];

    if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
      $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
      $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
      $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
      $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
      $bundles[] = new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle();
    }

    return $bundles;
  }

  public function getRootDir()
  {
    return __DIR__;
  }

  public function getCacheDir()
  {
    return dirname(__DIR__) . '/var/cache/' . $this->getEnvironment();
  }

  public function getLogDir()
  {
    return dirname(__DIR__) . '/var/logs';
  }

  public function registerContainerConfiguration(LoaderInterface $loader)
  {
    $loader->load($this->getRootDir() . '/config/config_' . $this->getEnvironment() . '.yml');
  }
}

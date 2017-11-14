<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class InstanceKernel extends Kernel
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

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return dirname(__DIR__).'/var/cache/'.$this->getEnvironment();
    }

    public function getLogDir()
    {
        return dirname(__DIR__).'/var/logs/'.$this->getEnvironment();
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        if ($this->debug)
        {
            $loader->load($this->getRootDir().'/config/'.$this->getEnvironment().'/config_dev.yml');
        }
        else
        {
            $loader->load($this->getRootDir().'/config/'.$this->getEnvironment().'/config.yml');
        }

    }
}

<?php

namespace App;

use App\BackOffice\BackOfficeInterface;
use App\DependencyInjection\Compiler\BackOfficePass;
use App\DependencyInjection\Compiler\PaymentGatewayPass;
use App\DependencyInjection\Compiler\PraticaFlowPass;
use App\DependencyInjection\Compiler\ProtocolloHandlerPass;
use App\DependencyInjection\Compiler\RouterPass;
use App\DependencyInjection\Compiler\SchedulableActionPass;
use App\DependencyInjection\Compiler\ServizioHandlerPass;
use App\Payment\PaymentDataInterface;
use App\Protocollo\ProtocolloHandlerInterface;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

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

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->addResource(new FileResource($this->getProjectDir().'/config/bundles.php'));
        $container->setParameter('container.dumper.inline_class_loader', \PHP_VERSION_ID < 70400 || $this->debug);
        $container->setParameter('container.dumper.inline_factories', true);
        $confDir = $this->getProjectDir().'/config';

        $loader->load($confDir.'/stanzadelcittadino'.self::CONFIG_EXTS, 'glob');

        $loader->load($confDir.'/{packages}/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{packages}/'.$this->environment.'/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}_'.$this->environment.self::CONFIG_EXTS, 'glob');
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = $this->getProjectDir().'/config';

        $routes->import($confDir.'/{routes}/'.$this->environment.'/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}'.self::CONFIG_EXTS, '/', 'glob');
    }

    protected function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RouterPass());

        $container->registerForAutoconfiguration(BackOfficeInterface::class)
            ->addTag('app.backoffice');
        $container->addCompilerPass(new BackOfficePass('app.backoffice'));

        $container->registerForAutoconfiguration(ProtocolloHandlerInterface::class)
            ->addTag('app.protocollo.handler');
        $container->addCompilerPass(new ProtocolloHandlerPass('app.protocollo.handler'));

        $container->addCompilerPass(new PraticaFlowPass('app.pratica.flow'));

        $container->addCompilerPass(new ServizioHandlerPass('app.servizio.handler'));

        $container->addCompilerPass(new SchedulableActionPass('app.schedule_action_handler'));

        $container->registerForAutoconfiguration(PaymentDataInterface::class)
            ->addTag('app.payment_gateway');
        $container->addCompilerPass(new PaymentGatewayPass('app.payment_gateway'));
    }
}

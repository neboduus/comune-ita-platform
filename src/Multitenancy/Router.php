<?php

namespace App\Multitenancy;

use Symfony\Bundle\FrameworkBundle\Routing\Router as BaseRouter;
use Symfony\Component\HttpFoundation\Request;

class Router extends BaseRouter implements TenantAwareInterface
{
    use TenantAwareTrait;

    /**
     * @param Request $request
     * @return array
     * @throws TenantNotFoundException
     */
    public function matchRequest(Request $request)
    {
        return $this->match($request->getPathInfo());
    }

    /**
     * @param $pathinfo
     * @return array
     * @throws TenantNotFoundException
     */
    public function match($pathinfo)
    {
        $originalPathInfo = $pathinfo;
        $pathinfo = $this->removeTenantPathinfo($pathinfo);

        $match = $this->getMatcher()->match($pathinfo);

        $this->validateTenantRequired($match);

        // controller is Symfony\Bundle\FrameworkBundle\Controller\RedirectController::urlRedirectAction
        if (isset($match['permanent']) && $originalPathInfo !== $pathinfo) {
            $match['path'] = $this->prependTenantPathinfo($match['path']);
        }

        return $match;
    }

    private function removeTenantPathinfo($pathinfo)
    {
        if ($this->hasTenant() && $this->getTenant()->hasPathInfoPrefix()) {
            $prefix = '/' . $this->getTenant()->getPathInfoPrefix();
            $length = mb_strlen($prefix);
            if ($length > 0 && 0 === strpos($pathinfo, $prefix)) {
                $pathinfo = substr($pathinfo, $length);
            }
        }

        return $pathinfo;
    }

    /**
     * @param $match
     * @throws TenantNotFoundException
     */
    private function validateTenantRequired($match)
    {
        $collection = $this->getRouteCollection();
        if (isset($match['_route']) && $route = $collection->get($match['_route'])) {
            if ($route->hasOption('tenant') && $route->getOption('tenant') == 'required' && !$this->hasTenant()) {
                throw new TenantNotFoundException();
            }
        }
    }

    private function prependTenantPathinfo($pathinfo)
    {
        if ($this->hasTenant() && $this->getTenant()->hasPathInfoPrefix()) {
            $prefix = '/' . $this->getTenant()->getPathInfoPrefix();
            $pathinfo = $prefix . $pathinfo;
        }

        return $pathinfo;
    }

    public function getGenerator()
    {
        if ($this->hasTenant() && $this->getTenant()->hasPathInfoPrefix()) {
            $this->setOption('generator_class', UrlGenerator::class);
            /** @var UrlGenerator $generator */
            $generator = parent::getGenerator();
            if ($this->hasTenant()) {
                $generator->setTenant($this->getTenant());
            }

            return $generator;
        }

        return parent::getGenerator();
    }
}

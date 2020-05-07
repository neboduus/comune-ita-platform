<?php

namespace App\Multitenancy\Listener\Request;

use App\Multitenancy\Doctrine\DBAL\TenantConnection;
use App\Multitenancy\Entity\Main\Tenant;
use App\Multitenancy\TenantAwareInterface;
use App\Multitenancy\TenantMatcher;
use App\Services\InstanceService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;

class TenantListener
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TenantConnection
     */
    private $tenantConnection;

    /**
     * @var TenantMatcher
     */
    private $matcher;

    /**
     * @var TenantAwareInterface
     */
    private $router;

    /**
     * @var InstanceService
     */
    private $instanceService;

    public function __construct(
        RequestStack $requestStack,
        TenantConnection $tenantConnection,
        TenantMatcher $matcher,
        RouterInterface $router,
        InstanceService $instanceService
    ) {
        $this->requestStack = $requestStack;
        $this->tenantConnection = $tenantConnection;
        $this->matcher = $matcher;
        $this->router = $router;
        $this->instanceService = $instanceService;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            $masterRequest = $this->requestStack->getMasterRequest();
            if ($masterRequest->attributes->has('_tenant')) {
                $tenant = $masterRequest->attributes->get('_tenant');
                if ($tenant instanceof Tenant) {
                    $event->getRequest()->attributes->set('_tenant', $tenant);
                    $this->switchTenant($tenant);
                    $this->setRouterContext($tenant);
                    return;
                }
            }
        }

        $request = $event->getRequest();
        $tenant = $this->getTenantFromRequest($request);

        if ($tenant instanceof Tenant) {
            $this->switchTenant($tenant);
            $request->attributes->set('_tenant', $tenant);
            $this->setRouterContext($tenant);
        }
    }

    private function switchTenant(Tenant $tenant)
    {
        $this->tenantConnection->changeParams(
            $tenant->getDbHost(),
            $tenant->getDbPort(),
            $tenant->getDbName(),
            $tenant->getDbUser(),
            $tenant->getDbPassword()
        );
        $this->instanceService->setTenant($tenant);
    }

    private function setRouterContext(Tenant $tenant)
    {
        if (null !== $this->router) {
            $this->router->setTenant($tenant);
        }
    }

    private function getTenantFromRequest(Request $request)
    {
        return $this->matcher->matchFromRequest($request);
    }
}

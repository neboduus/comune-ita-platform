<?php

namespace App\Multitenancy;

use App\Multitenancy\Entity\Main\Tenant;
use App\Multitenancy\Repository\TenantRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class TenantMatcher
{
    /**
     * @var TenantRepository
     */
    private $tenantRepository;

    /**
     * @var Router
     */
    private $router;

    /**
     * TenantListener constructor.
     * @param TenantRepository $tenantRepository
     * @param $router
     */
    public function __construct(
        TenantRepository $tenantRepository,
        RouterInterface $router
    ) {
        $this->tenantRepository = $tenantRepository;
        $this->router = $router;
    }

    /**
     * @param Request $request
     * @return Tenant|null
     */
    public function matchFromRequest(Request $request)
    {
        $context = $this->router->getContext()->fromRequest($request);
        $host = $context->getHost();
        $pathInfoParts = explode('/', trim($context->getPathInfo(), '/'));
        $pathInfoPrefix = isset($pathInfoParts[0]) ? $pathInfoParts[0] : '';

        if (!empty($pathInfoPrefix)) {
            return $this->tenantRepository->findOneBy(['pathInfoPrefix' => $pathInfoPrefix]);
        }

        return $this->tenantRepository->findOneBy(['host' => $host]);
    }

    public function matchFromIdentifier($identifier)
    {
        return $this->tenantRepository->findOneBy(['slug' => $identifier]);
    }
}

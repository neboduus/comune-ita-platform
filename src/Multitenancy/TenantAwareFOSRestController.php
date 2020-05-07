<?php

namespace App\Multitenancy;

use App\Multitenancy\Entity\Main\Tenant;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class TenantAwareFOSRestController extends AbstractFOSRestController implements TenantAwareInterface
{
    /**
     * @var Tenant
     */
    protected $tenant;

    public function getTenant()
    {
        if ($this->tenant === null) {
            $this->tenant = false;
            $masterRequest = $this->get('request_stack')->getMasterRequest();

            if ($masterRequest->attributes->has('_tenant')) {
                $tenant = $masterRequest->attributes->get('_tenant');
                if ($tenant instanceof Tenant) {
                    $this->setTenant($tenant);
                }
            }
        }

        return $this->tenant;
    }

    public function setTenant(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    public function hasTenant(): bool
    {
        return $this->hasTenant() instanceof Tenant;
    }
}

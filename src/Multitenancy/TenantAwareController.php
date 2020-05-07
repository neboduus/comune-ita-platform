<?php

namespace App\Multitenancy;

use App\Multitenancy\Entity\Main\Tenant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

abstract class TenantAwareController extends AbstractController implements TenantAwareInterface
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
                    $this->tenant = $tenant;
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
        return $this->getTenant() instanceof Tenant;
    }
}

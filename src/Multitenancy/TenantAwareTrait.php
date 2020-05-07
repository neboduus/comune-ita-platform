<?php

namespace App\Multitenancy;

use App\Multitenancy\Entity\Main\Tenant;

trait TenantAwareTrait
{
    /**
     * @var Tenant
     */
    protected $tenant;

    /**
     * @return Tenant|null
     */
    public function getTenant()
    {
        return $this->tenant;
    }

    public function setTenant(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    public function hasTenant(): bool
    {
        return $this->tenant instanceof Tenant;
    }
}

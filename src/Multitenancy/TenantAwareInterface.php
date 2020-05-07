<?php

namespace App\Multitenancy;

use App\Multitenancy\Entity\Main\Tenant;

interface TenantAwareInterface
{
    public function getTenant();

    public function setTenant(Tenant $tenant);

    public function hasTenant(): bool;
}

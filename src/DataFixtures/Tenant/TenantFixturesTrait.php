<?php

namespace App\DataFixtures\Tenant;

use App\Multitenancy\Listener\Command\FixturesListener;

trait TenantFixturesTrait
{
    public static function getGroups(): array
    {
        return [FixturesListener::TENANT_GROUP];
    }
}

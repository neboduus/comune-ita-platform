<?php

namespace App\Multitenancy\Listener\Controller;

use App\Multitenancy\Annotations\MustHaveTenant;
use App\Multitenancy\Entity\Main\Tenant;
use App\Multitenancy\TenantNotFoundException;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class ControllerListener
{
    /**
     * @param ControllerEvent $event
     * @throws TenantNotFoundException
     */
    public function onKernelController(ControllerEvent $event)
    {
        $request = $event->getRequest();
        $mustHaveTenant = $request->attributes->get('_must_have_tenant');

        if (!$mustHaveTenant instanceof MustHaveTenant) {
            return;
        }

        $tenant = null;
        if ($request->attributes->has('_tenant')) {
            $tenant = $request->attributes->get('_tenant');
        }

        if (!$tenant instanceof Tenant) {
            throw new TenantNotFoundException();
        }
    }
}

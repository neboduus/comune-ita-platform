<?php

namespace App\DataCollector;

use App\Multitenancy\Entity\Main\Tenant;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class TenantDataCollector extends DataCollector
{
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = [
            'tenant' => '',
            'db' => '',
            'prefix' => '',
            'protocollo' => '',
        ];

        if ($request->attributes->has('_tenant')) {
            $tenant = $request->attributes->get('_tenant');
            if ($tenant instanceof Tenant) {
                $this->data = [
                    'tenant' => $tenant->getName(),
                    'db' => $tenant->getDbName(),
                    'prefix' => $tenant->getPathInfoPrefix(),
                    'protocollo' => $tenant->getProtocolloHandler(),
                ];
            }
        }
    }

    public function reset()
    {
        $this->data = [];
    }

    public function getName()
    {
        return 'app.tenant_collector';
    }

    public function getTenant()
    {
        return $this->data['tenant'];
    }

    public function getDb()
    {
        return $this->data['db'];
    }

    public function getPrefix()
    {
        return $this->data['prefix'];
    }

    public function getProtocollo()
    {
        return $this->data['protocollo'];
    }
}

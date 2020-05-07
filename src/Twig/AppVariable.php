<?php

namespace App\Twig;

use App\Services\InstanceService;
use App\Services\BackOfficeCollection;

class AppVariable extends \Symfony\Bridge\Twig\AppVariable
{
    /**
     * @var InstanceService
     */
    private $instanceService;

    /**
     * @var BackOfficeCollection
     */
    private $backoffices;

    /**
     * @return InstanceService
     */
    public function getInstanceService()
    {
        return $this->instanceService;
    }

    /**
     * @param InstanceService $instanceService
     */
    public function setInstanceService($instanceService): void
    {
        $this->instanceService = $instanceService;
    }

    /**
     * @return BackOfficeCollection
     */
    public function getBackoffices(): BackOfficeCollection
    {
        return $this->backoffices;
    }

    /**
     * @param BackOfficeCollection $backoffices
     */
    public function setBackoffices(BackOfficeCollection $backoffices): void
    {
        $this->backoffices = $backoffices;
    }

    public function hasEndpointPrefix()
    {
        return $this->getInstanceService()->hasTenant() && !empty($this->getInstanceService()->getTenant()->getPathInfoPrefix());
    }

    public function getEndpointPrefix()
    {
        if ($this->hasEndpointPrefix()) {
            return $this->getInstanceService()->getTenant()->getPathInfoPrefix();
        }

        return false;
    }
}
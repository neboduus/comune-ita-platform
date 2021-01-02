<?php

namespace App\Tests\Base;

use Symfony\Bundle\FrameworkBundle\Client;

class InstanceAwareClient extends Client
{
    /**
     * @var \AppTestKernel
     */
    protected $kernel;

    public function request($method, $uri, array $parameters = array(), array $files = array(), array $server = array(), $content = null, $changeHistory = true)
    {
        $prefix = '/' . $this->kernel->getIdentifier();
        if (strpos($uri, $prefix) === false) {
            $uri = $prefix . $uri;
        }
        return parent::request($method, $uri, $parameters, $files, $server, $content, $changeHistory);
    }
}

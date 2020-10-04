<?php

namespace App\Services;

use Symfony\Bridge\Doctrine\RegistryInterface;

class InstanceService
{

    private $instance;

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * TermsAcceptanceCheckerService constructor.
     * @param RegistryInterface $doctrine
     */
    public function __construct(RegistryInterface $doctrine, $instance)
    {
        $this->doctrine = $doctrine;
        $this->instance = $instance;
    }

    /**
     * @return \AppBundle\Entity\Ente|bool
     */
    public function getCurrentInstance()
    {
        if ($this->instance == null)
        {
            return false;
        }
        $repo = $this->doctrine->getRepository('AppBundle:Ente');
        $ente = $repo->findOneBy(array('slug' => $this->instance));
        return $ente;
    }
}

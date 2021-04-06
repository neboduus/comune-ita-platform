<?php

namespace AppBundle\Services;

use AppBundle\Entity\Ente;
use AppBundle\Entity\Servizio;
use RuntimeException;
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
   * @param string $instance
   */
  public function __construct(RegistryInterface $doctrine, $instance)
  {
    $this->doctrine = $doctrine;
    $this->instance = $instance;
  }

  public function hasInstance()
  {
    return !empty($this->instance);
  }

  /**
   * @return Ente|object|null
   * @throws RuntimeException
   */
  public function getCurrentInstance()
  {
    if (empty($this->instance)) {
      throw new RuntimeException("Ente not configured");
    }

    $repo = $this->doctrine->getRepository('AppBundle:Ente');
    $ente = $repo->findOneBy(array('slug' => $this->instance));
    if (!$ente instanceof Ente) {
      throw new RuntimeException("Ente $this->instance not found");
    }

    return $ente;
  }

  /**
   * @return array
   */
  public function getServices(): array
  {
    $erogatori = $this->getCurrentInstance()->getErogatori()->toArray();
    $services = [];
    foreach($erogatori as $erogatore) {
      $serviziErogati = $erogatore->getServizi()->toArray();
      $services = array_merge($services, $serviziErogati);
    }
    return $services;
  }
}

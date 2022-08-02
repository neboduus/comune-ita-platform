<?php

namespace App\Services;

use App\Entity\Ente;
use App\Entity\Servizio;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

class InstanceService
{

  private $instance;

  /**
   * @var EntityManagerInterface
   */
  private $entityManager;

  /**
   * TermsAcceptanceCheckerService constructor.
   * @param EntityManagerInterface $doctrine
   * @param string $instance
   */
  public function __construct(EntityManagerInterface $doctrine, $instance)
  {
    $this->entityManager = $doctrine;
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

    $repo = $this->entityManager->getRepository('App:Ente');
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

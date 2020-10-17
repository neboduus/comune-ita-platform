<?php

namespace App\Services;

use App\Entity\Ente;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;

class InstanceService
{

  private $instance;

  /**@var ManagerRegistry */
  private $doctrine;

  /**
   * TermsAcceptanceCheckerService constructor.
   * @param ManagerRegistry $doctrine
   */
  public function __construct(ManagerRegistry $doctrine, $instance)
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

    $repo = $this->doctrine->getRepository('App:Ente');
    $ente = $repo->findOneBy(array('slug' => $this->instance));
    if (!$ente instanceof Ente) {
      throw new RuntimeException("Ente $this->instance not found");
    }

    return $ente;
  }

  /**
   * @return string
   */
  public function getPrefix()
  {
    return $this->getCurrentInstance()->getSlug();
  }
}

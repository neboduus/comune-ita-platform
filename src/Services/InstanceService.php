<?php

namespace App\Services;

use Doctrine\Persistence\ManagerRegistry;

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

  /**
   * @return \App\Entity\Ente|bool
   */
  public function getCurrentInstance()
  {
    if ($this->instance == null) {
      return false;
    }
    $repo = $this->doctrine->getRepository('App:Ente');
    $ente = $repo->findOneBy(array('slug' => $this->instance));

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

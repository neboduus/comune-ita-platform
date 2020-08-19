<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

class ServiceGroupRepository extends EntityRepository
{
  public function findStickyAvailable()
  {
    $qb = $this->createQueryBuilder('s')
      ->where('s.sticky = true')
      ->orderBy('s.name', 'ASC');

    return $qb->getQuery()->getResult();
  }

  public function findNotStickyAvailable()
  {
    $qb = $this->createQueryBuilder('s')
      ->where('s.sticky = false OR s.sticky IS NULL')
      ->orderBy('s.name', 'ASC');

    return $qb->getQuery()->getResult();
  }
}

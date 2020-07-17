<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

class ServizioRepository extends EntityRepository
{
  public function findStickyAvailable()
  {
    $qb = $this->createQueryBuilder('s')
      ->where('s.status NOT IN (:notAvailableStatues)')
      ->setParameter('notAvailableStatues', [Servizio::STATUS_CANCELLED, Servizio::STATUS_PRIVATE])
      ->andWhere('s.sticky = true')
      ->orderBy('s.name', 'ASC');

    return $qb->getQuery()->getResult();
  }

  public function findNotStickyAvailable()
  {
    $qb = $this->createQueryBuilder('s')
      ->where('s.status NOT IN (:notAvailableStatues)')
      ->setParameter('notAvailableStatues', [Servizio::STATUS_CANCELLED, Servizio::STATUS_PRIVATE])
      ->andWhere('s.sticky = false OR s.sticky IS NULL')
      ->orderBy('s.name', 'ASC');

    return $qb->getQuery()->getResult();
  }
}

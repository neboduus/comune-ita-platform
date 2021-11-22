<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

class ServiceGroupRepository extends EntityRepository
{

  public function findByCriteria($criteria = [])
  {
    $qb = $this->createQueryBuilder('s');

    // topics
    if (isset($criteria['topics'])) {
      $qb
        ->andWhere('s.topics = :topics')
        ->setParameter('topics', $criteria['topics']);
    }

    if (isset($criteria['recipients'])) {
      $qb
        ->leftJoin('s.recipients', 'recipients')
        ->andWhere('recipients.id = :recipients')
        ->setParameter('recipients', $criteria['recipients']);
    }

    $qb->orderBy('s.name', 'ASC');

    return $qb->getQuery()->getResult();
  }

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

  public function findSharedServices()
  {
    $qb = $this->createQueryBuilder('s')
      ->where('s.sticky = false OR s.sticky IS NULL')
      ->orderBy('s.name', 'ASC');

    return $qb->getQuery()->getResult();
  }
}

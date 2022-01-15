<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

class ServiceGroupRepository extends EntityRepository
{

  public function findByCriteria($criteria = [])
  {
    $qb = $this->createQueryBuilder('s');

    /*$qb = $this->createQueryBuilder('s')
      ->select('s', 'count(services) AS services_count')
      ->leftJoin('s.services', 'services')
      ->where('services.status IN (:status)')
      ->setParameter('status', Servizio::PUBLIC_STATUSES)
      ->groupBy('s.id')
    ;*/

    // topics
    if (isset($criteria['topics'])) {
      $qb
        ->andWhere('s.topics = :topics')
        ->setParameter('topics', $criteria['topics']);
    }

    // Recipients
    if (isset($criteria['recipients'])) {
      $qb
        ->leftJoin('s.recipients', 'recipients')
        ->andWhere('recipients.id = :recipients')
        ->setParameter('recipients', $criteria['recipients']);
    }

    // GeographicAreas
    if (isset($criteria['geographic_areas'])) {
      $qb
        ->leftJoin('s.geographicAreas', 'geographicAreas')
        ->andWhere('geographicAreas.id = :geographic_areas')
        ->setParameter('geographic_areas', $criteria['geographic_areas']);
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

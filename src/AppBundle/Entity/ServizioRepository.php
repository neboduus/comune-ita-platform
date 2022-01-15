<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;

class ServizioRepository extends EntityRepository
{

  public function findByCriteria($criteria)
  {

    // grouped
    if (isset($criteria['grouped']) && !$criteria['grouped']) {
      return $this->findNotSharedByCriteria($criteria);
    } else {
      $results = [];

      $notShared = $this->findNotSharedByCriteria($criteria);
      foreach ($notShared as $item) {
        $results[$item->getSlug()]= $item;
      }

      $shared = $this->findSharedByCriteria($criteria);
      foreach ($shared as $item) {
        $results[$item->getSlug()]= $item;
      }

      ksort($results);
      return $results;
    }
  }

  private function findNotSharedByCriteria($criteria)
  {

    $qb = $this->createQueryBuilder('s')
    ->where('s.sharedWithGroup = :sharedWithGroup')
    ->setParameter('sharedWithGroup', false);

    // Status
    if (isset($criteria['status'])) {
      $qb
        ->andWhere('s.status IN (:status)')
        ->setParameter('status', $criteria['status']);
    }

    // grouped
    if (isset($criteria['grouped']) && !$criteria['grouped']) {
      $qb->andWhere('s.serviceGroup IS NULL');
    } else {
      // serviceGroup
      if (isset($criteria['serviceGroup'])) {
        $qb
          ->andWhere('s.serviceGroup = :serviceGroup')
          ->setParameter('serviceGroup', $criteria['serviceGroup']);
      }
    }

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

  private function findSharedByCriteria($criteria)
  {

    $qb = $this->createQueryBuilder('s')
      ->leftJoin('s.serviceGroup', 'serviceGroup')
      ->where('s.serviceGroup IS NOT NULL')
      ->andWhere('s.sharedWithGroup = :sharedWithGroup')
      ->setParameter('sharedWithGroup', true);

    // Status
    if (isset($criteria['status'])) {
      $qb
        ->andWhere('s.status IN (:status)')
        ->setParameter('status', $criteria['status']);
    }

    // serviceGroup
    if (isset($criteria['serviceGroup'])) {
      $qb
        ->andWhere('s.serviceGroup = :serviceGroup')
        ->setParameter('serviceGroup', $criteria['serviceGroup']);
    }

    // topics
    if (isset($criteria['topics'])) {
      $qb
        ->andWhere('serviceGroup.topics = :topics')
        ->setParameter('topics', $criteria['topics']);
    }

    // Recipients
    if (isset($criteria['recipients'])) {
      $qb
        ->leftJoin('serviceGroup.recipients', 'recipients')
        ->andWhere('recipients.id = :recipients')
        ->setParameter('recipients', $criteria['recipients']);
    }

    // GeographicAreas
    if (isset($criteria['geographic_areas'])) {
      $qb
        ->leftJoin('serviceGroup.geographicAreas', 'geographicAreas')
        ->andWhere('geographicAreas.id = :geographic_areas')
        ->setParameter('geographic_areas', $criteria['geographic_areas']);
    }

    $qb->orderBy('s.name', 'ASC');

    return $qb->getQuery()->getResult();
  }

  public function findAvailable($criteria = [])
  {

    $criteria['grouped'] = false;
    $criteria['status'] = Servizio::PUBLIC_STATUSES;

    /*$qb = $this->createQueryBuilder('s')
      ->where('s.status NOT IN (:notAvailableStatues)')
      ->setParameter('notAvailableStatues', [Servizio::STATUS_CANCELLED, Servizio::STATUS_PRIVATE])
      ->andWhere('s.serviceGroup IS NULL')
      ->orderBy('s.name', 'ASC');
    return $qb->getQuery()->getResult();*/
    return $this->findByCriteria($criteria);
  }

  public function findStickyAvailable(int $limit = null)
  {
    $qb = $this->createQueryBuilder('s')
      ->where('s.status NOT IN (:notAvailableStatues)')
      ->setParameter('notAvailableStatues', [Servizio::STATUS_CANCELLED, Servizio::STATUS_PRIVATE])
      ->andWhere('s.sticky = true')
      ->andWhere('s.serviceGroup IS NULL')
      ->orderBy('s.name', 'ASC');

    if ($limit){
      $qb->setMaxResults($limit);
    }

    return $qb->getQuery()->getResult();
  }

  public function findNotStickyAvailable()
  {
    $qb = $this->createQueryBuilder('s')
      ->where('s.status NOT IN (:notAvailableStatues)')
      ->setParameter('notAvailableStatues', [Servizio::STATUS_CANCELLED, Servizio::STATUS_PRIVATE])
      ->andWhere('s.sticky = false OR s.sticky IS NULL')
      ->andWhere('s.serviceGroup IS NULL')
      ->orderBy('s.name', 'ASC');

    return $qb->getQuery()->getResult();
  }
}

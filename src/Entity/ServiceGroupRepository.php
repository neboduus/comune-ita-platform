<?php

namespace App\Entity;

use App\Model\Service;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

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
  public function hasServicesWithMaxResponseTime($groupId)
  {
    $qb = $this->getEntityManager()->createQueryBuilder();
    $qb
      ->select($qb->expr()->count("s.maxResponseTime"))
      ->from("App:Servizio", "s")
      ->where(
        $qb->expr()->andX(
          $qb->expr()->eq("s.serviceGroup", ":groupId"),
          $qb->expr()->eq("s.sharedWithGroup", "true"),
          $qb->expr()->eq("s.workflow", Servizio::WORKFLOW_APPROVAL)
        )
      )
      ->setParameter('groupId', $groupId);
    $qbResult = $qb->getQuery()->getSingleScalarResult();
    return $qbResult > 0;
  }

  /**
   * @param $groupId
   * @return bool
   * @throws NoResultException
   * @throws NonUniqueResultException
   */
  public function hasScheduledServices($groupId): bool
  {
    $qb = $this->getEntityManager()->createQueryBuilder();
    $qb
      ->select($qb->expr()->count("s.id"))
      ->from("App:Servizio", "s")
      ->where(
        $qb->expr()->andX(
          $qb->expr()->eq("s.serviceGroup", ":groupId"),
          $qb->expr()->eq("s.status", Servizio::STATUS_SCHEDULED),
        )
      )
      ->setParameter('groupId', $groupId);
    $qbResult = $qb->getQuery()->getSingleScalarResult();
    return $qbResult > 0;
  }

}

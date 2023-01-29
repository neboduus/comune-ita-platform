<?php

namespace App\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query;

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

      if ($criteria['ascending']) {
        ksort($results);
      }    

      return $results;
    }
  }

  private function findNotSharedByCriteria($criteria)
  {
    $qb = $this->createQueryBuilder('s')
    ->where('s.sharedWithGroup = :sharedWithGroup')
    ->setParameter('sharedWithGroup', false)
    ->orWhere('s.sharedWithGroup IS NULL');

    // Search text
    if (isset($criteria['q'])) {
      $qb
        ->andWhere($qb->expr()->like('LOWER(s.name)', ':q'))
        ->setParameter('q', '%' . strtolower($criteria['q']) . '%');
    }

    // Status
    if (isset($criteria['status'])) {
      $qb
        ->andWhere('s.status IN (:status)')
        ->setParameter('status', $criteria['status']);
    }

    // identifier
    if (isset($criteria['identifier'])) {
      $qb
        ->andWhere('s.identifier = :identifier')
        ->setParameter('identifier', $criteria['identifier']);
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
        ->andWhere('s.topics IN (:topics)')
        ->setParameter('topics', $criteria['topics']);
    }

    // Recipients
    if (isset($criteria['recipients'])) {
      $qb
        ->leftJoin('s.recipients', 'recipients')
        ->andWhere('recipients.id IN (:recipients)')
        ->setParameter('recipients', $criteria['recipients']);
    }

    // GeographicAreas
    if (isset($criteria['geographic_areas'])) {
      $qb
        ->leftJoin('s.geographicAreas', 'geographicAreas')
        ->andWhere('geographicAreas.id IN (:geographic_areas)')
        ->setParameter('geographic_areas', $criteria['geographic_areas']);
    }

    // sticky
    if ($criteria['sticky'] !== null) {
      $qb->andWhere($criteria['sticky'] ? 's.sticky = true' : 's.sticky = false OR s.sticky IS NULL');
    }

    $qb->orderBy('s.' . $criteria['order_by'], $criteria['ascending'] ? 'ASC' : 'DESC');

    if (isset($criteria['limit'])) {
      $qb->setMaxResults($criteria['limit']);
    }

    return $qb->getQuery()
      ->setHint(
        Query::HINT_CUSTOM_OUTPUT_WALKER,
        'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
      )
      ->setHint(
        \Gedmo\Translatable\TranslatableListener::HINT_TRANSLATABLE_LOCALE,
        $criteria['locale']
      )
      ->setHint(\Gedmo\Translatable\TranslatableListener::HINT_FALLBACK, 1)
      ->getResult();
  }

  private function findSharedByCriteria($criteria)
  {
    $qb = $this->createQueryBuilder('s')
      ->leftJoin('s.serviceGroup', 'serviceGroup')
      ->where('s.serviceGroup IS NOT NULL')
      ->andWhere('s.sharedWithGroup = :sharedWithGroup')
      ->setParameter('sharedWithGroup', true);

    // Search text
    if (isset($criteria['q'])) {
      $qb
        ->andWhere($qb->expr()->like('LOWER(s.name)', ':q'))
        ->setParameter('q', '%' . strtolower($criteria['q']) . '%');
    }

    // Status
    if (isset($criteria['status'])) {
      $qb
        ->andWhere('s.status IN (:status)')
        ->setParameter('status', $criteria['status']);
    }

    // identifier
    if (isset($criteria['identifier'])) {
      $qb
        ->andWhere('s.identifier = :identifier')
        ->setParameter('identifier', $criteria['identifier']);
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
        ->andWhere('serviceGroup.topics IN (:topics)')
        ->setParameter('topics', $criteria['topics']);
    }

    // Recipients
    if (isset($criteria['recipients'])) {
      $qb
        ->leftJoin('serviceGroup.recipients', 'recipients')
        ->andWhere('recipients.id IN (:recipients)')
        ->setParameter('recipients', $criteria['recipients']);
    }

    // GeographicAreas
    if (isset($criteria['geographic_areas'])) {
      $qb
        ->leftJoin('serviceGroup.geographicAreas', 'geographicAreas')
        ->andWhere('geographicAreas.id IN (:geographic_areas)')
        ->setParameter('geographic_areas', $criteria['geographic_areas']);
    }
    
    // sticky
    if ($criteria['sticky'] != null) {
      $qb->andWhere($criteria['sticky'] ? 's.sticky = true' : 's.sticky = false OR s.sticky IS NULL');
    }

    $qb->orderBy('s.' . $criteria['order_by'], $criteria['ascending'] ? 'ASC' : 'DESC');

    if (isset($criteria['limit'])) {
      $qb->setMaxResults($criteria['limit']);
    }

    return $qb->getQuery()
      ->setHint(
        Query::HINT_CUSTOM_OUTPUT_WALKER,
        'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
      )
      ->setHint(
        \Gedmo\Translatable\TranslatableListener::HINT_TRANSLATABLE_LOCALE,
        $criteria['locale']
      )
      ->setHint(\Gedmo\Translatable\TranslatableListener::HINT_FALLBACK, 1)
      ->getResult();
  }

  public function findAvailable($criteria = [])
  {

    $criteria['grouped'] = false;
    $criteria['status'] = Servizio::PUBLIC_STATUSES;
    return $this->findByCriteria($criteria);
  }

  public function findStickyAvailable(string $orderBy = 'name', bool $ascending = true, int $limit = null)
  {
    $qb = $this->createQueryBuilder('s')
      ->where('s.status NOT IN (:notAvailableStatues)')
      ->setParameter('notAvailableStatues', [Servizio::STATUS_CANCELLED, Servizio::STATUS_PRIVATE])
      ->andWhere('s.sticky = true')
      ->andWhere('s.serviceGroup IS NULL')
      ->orderBy('s.' . $orderBy, $ascending ? 'ASC' : 'DESC');

    if ($limit){
      $qb->setMaxResults($limit);
    }

    return $qb->getQuery()->getResult();
  }

  public function findNotStickyAvailable(string $orderBy = 'name', bool $ascending = true, int $limit = null)
  {
    $qb = $this->createQueryBuilder('s')
      ->where('s.status NOT IN (:notAvailableStatues)')
      ->setParameter('notAvailableStatues', [Servizio::STATUS_CANCELLED, Servizio::STATUS_PRIVATE])
      ->andWhere('s.sticky = false OR s.sticky IS NULL')
      ->andWhere('s.serviceGroup IS NULL')
      ->orderBy('s.' . $orderBy, $ascending ? 'ASC' : 'DESC');

    if ($limit){
      $qb->setMaxResults($limit);
    }

    return $qb->getQuery()->getResult();
  }

  public function findAvailableForSubscriptionPaymentSettings()
  {
    $qb = $this->createQueryBuilder('s')
      ->where('s.paymentRequired IS NOT NULL')
      ->andWhere('s.integrations IS NOT NULL')
      ->andWhere('s.status NOT IN (:notAvailableStatues)')
      ->setParameter('notAvailableStatues', [Servizio::STATUS_CANCELLED, Servizio::STATUS_SUSPENDED])
      ->orderBy('s.name', 'ASC');

    return $qb->getQuery()->getResult();
  }

  public function getNotStickyCount()
  {
    $qb = $this->createQueryBuilder('s')
      ->select('count(s.id)')
      ->where('s.status NOT IN (:notAvailableStatues)')
      ->setParameter('notAvailableStatues', [Servizio::STATUS_CANCELLED, Servizio::STATUS_PRIVATE])
      ->andWhere('s.sticky = false OR s.sticky IS NULL')
      ->andWhere('s.serviceGroup IS NULL');
    
    return $qb->getQuery()->getSingleScalarResult();
  }

}

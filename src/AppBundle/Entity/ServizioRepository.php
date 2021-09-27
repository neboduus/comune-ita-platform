<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;

class ServizioRepository extends EntityRepository
{
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

  public function findOneI18n($id, $locale)
  {
    $qb = $this->createQueryBuilder('s')
      ->select('s')
      ->where('s.id = :id')
      ->setMaxResults(1)
      ->setParameter('id', $id);
    ;

    $query = $qb->getQuery();

    $query->setHint(
      \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
      'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
    );

    // force Gedmo Translatable to not use current locale
    $query->setHint(
      \Gedmo\Translatable\TranslatableListener::HINT_TRANSLATABLE_LOCALE,
      $locale
    );

    $query->setHint(
      \Gedmo\Translatable\TranslatableListener::HINT_FALLBACK,
      1
    );

    return $query->getSingleResult();
  }
}

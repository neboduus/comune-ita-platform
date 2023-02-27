<?php

namespace App\Repository;

use App\Entity\UserGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserGroup>
 *
 * @method UserGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserGroup[]    findAll()
 * @method UserGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserGroupRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, UserGroup::class);
  }

  /**
   * @return UserGroup[] Returns an array of UserGroup objects
   *
   */
  public function findByCriteria(array $criteria)
  {
    $builder = $this->createQueryBuilder('u')
      ->orderBy('u.name', 'ASC');

    if (isset($criteria['has_calendar'])) {
      if ($criteria['has_calendar']) {
        $builder->andWhere('u.calendar IS NOT NULL');
      } else {
        $builder->andWhere('u.calendar IS NULL');
      }
    }

    if (isset($criteria['service_id'])) {
      $builder
        ->leftJoin('u.services', 'services')
        ->andWhere('services.id = :service')
        ->setParameter('service', $criteria['service_id']);
    }

    return $builder->getQuery()->getResult();
  }


  /*
  public function findOneBySomeField($value): ?UserGroup
  {
      return $this->createQueryBuilder('p')
          ->andWhere('p.exampleField = :val')
          ->setParameter('val', $value)
          ->getQuery()
          ->getOneOrNullResult()
      ;
  }
  */
}

<?php

namespace App\Repository;

use App\Entity\Categoria;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Categoria>
 *
 * @method Categoria|null find($id, $lockMode = null, $lockVersion = null)
 * @method Categoria|null findOneBy(array $criteria, array $orderBy = null)
 * @method Categoria[]    findAll()
 * @method Categoria[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoriaRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Categoria::class);
  }

  /**
   * @throws ORMException
   * @throws OptimisticLockException
   */
  public function add(Categoria $entity, bool $flush = true): void
  {
    $this->_em->persist($entity);
    if ($flush) {
      $this->_em->flush();
    }
  }

  /**
   * @throws ORMException
   * @throws OptimisticLockException
   */
  public function remove(Categoria $entity, bool $flush = true): void
  {
    $this->_em->remove($entity);
    if ($flush) {
      $this->_em->flush();
    }
  }

  /**
   * @return Categoria[] Returns an array of Categoria objects
   *
   */
  public function findFirstLevelCategories()
  {
    return $this->createQueryBuilder('c')
      ->andWhere('c.parent IS NULL')
      ->orderBy('c.name', 'ASC')
      ->getQuery()
      ->getResult();
  }


  /*
  public function findOneBySomeField($value): ?Categoria
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

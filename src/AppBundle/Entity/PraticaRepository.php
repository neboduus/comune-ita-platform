<?php

namespace AppBundle\Entity;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityRepository;

/**
 * PraticaRepository
 *
 * This class was generated by the PhpStorm "Php Annotations" Plugin. Add your own custom
 * repository methods below.
 */
class PraticaRepository extends EntityRepository
{
  const OPERATORI_LOWER_STATE = Pratica::STATUS_PRE_SUBMIT;

  private $classConstants;

  public function findRelatedPraticaForUser(CPSUser $user)
  {
    $sql = 'SELECT id from pratica where (related_cfs)::jsonb @> \'"'.$user->getCodiceFiscale().'"\'';


    $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll();

    $ids = [];

    foreach ($result as $id) {
      $ids[] = $id['id'];
    }

    return $this->findById($ids);
  }

  public function findDraftPraticaForUser(CPSUser $user)
  {
    return $this->findBy(
      [
        'user' => $user,
        'status' => Pratica::STATUS_DRAFT,
      ],
      [
        'creationTime' => 'DESC',
      ]
    );
  }

  public function findPendingPraticaForUser(CPSUser $user)
  {
    return $this->findBy(
      [
        'user' => $user,
        'status' => [
          Pratica::STATUS_PRE_SUBMIT,
          Pratica::STATUS_SUBMITTED,
          Pratica::STATUS_REGISTERED,
          Pratica::STATUS_PENDING,
          Pratica::STATUS_PENDING_AFTER_INTEGRATION,
          Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE,
          Pratica::STATUS_REQUEST_INTEGRATION,
          Pratica::STATUS_REGISTERED_AFTER_INTEGRATION,
          Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE,
        ],
      ],
      [
        'creationTime' => 'DESC',
      ]
    );
  }

  public function findProcessingPraticaForUser(CPSUser $user)
  {
    return $this->findBy(
      [
        'user' => $user,
        'status' => Pratica::STATUS_PROCESSING,
      ],
      [
        'creationTime' => 'DESC',
      ]
    );
  }

  public function findCompletePraticaForUser(CPSUser $user)
  {
    return $this->findBy(
      [
        'user' => $user,
        'status' => Pratica::STATUS_COMPLETE,
      ],
      [
        'creationTime' => 'DESC',
      ]
    );
  }

  public function findCancelledPraticaForUser(CPSUser $user)
  {
    return $this->findBy(
      [
        'user' => $user,
        'status' => [
          Pratica::STATUS_CANCELLED,
        ],
      ],
      [
        'creationTime' => 'DESC',
      ]
    );
  }

  public function findDraftForIntegrationPraticaForUser(CPSUser $user)
  {
    return $this->findBy(
      [
        'user' => $user,
        'status' => [
          Pratica::STATUS_DRAFT_FOR_INTEGRATION,
          Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION,
        ],
      ],
      [
        'creationTime' => 'DESC',
      ]
    );
  }

  public function findPraticheAssignedToOperatore(OperatoreUser $user)
  {
    $ente = $user->getEnte();

    return $this->findBy(
      [
        'operatore' => $user,
        'erogatore' => $ente->getErogatori()->toArray(),
        'status' => [
          Pratica::STATUS_PENDING,
          Pratica::STATUS_PENDING_AFTER_INTEGRATION,
          Pratica::STATUS_PROCESSING,
          Pratica::STATUS_SUBMITTED,
          Pratica::STATUS_REGISTERED,
        ],
      ]
    );
  }

  public function findPraticheByEnte(Ente $ente)
  {
    return $this->findBy(
      [
        'erogatore' => $ente->getErogatori()->toArray(),
      ]
    );
  }

  public function findSubmittedPraticheByEnte(Ente $ente)
  {
    $qb = $this->createQueryBuilder('p');
    $qb->where('p.status >= '.Pratica::STATUS_SUBMITTED)
      ->andWhere('p.ente = :ente')
      ->setParameter('ente', $ente);


    return $qb->getQuery()->getResult();
  }

  public function findPraticheUnAssignedByEnte(Ente $ente)
  {
    return $this->findBy(
      [
        'operatore' => null,
        'erogatore' => $ente->getErogatori()->toArray(),
        'status' => [
          Pratica::STATUS_PENDING,
          Pratica::STATUS_SUBMITTED,
          Pratica::STATUS_REGISTERED,
          Pratica::STATUS_PROCESSING,
        ],
      ]
    );
  }

  public function findPraticheCompletedByOperatore(OperatoreUser $user)
  {
    $ente = $user->getEnte();

    return $this->findBy(
      [
        'operatore' => $user,
        'erogatore' => $ente->getErogatori()->toArray(),
        'status' => [
          Pratica::STATUS_COMPLETE,
          Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE,
          Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE,
          Pratica::STATUS_CANCELLED,
        ],
      ]
    );
  }

  public function findPraticheByOperatore(OperatoreUser $user, $filters, $limit, $offset)
  {
    $serviziAbilitati = $user->getServiziAbilitati()->toArray();
    if (empty($serviziAbilitati)){
      return [];
    }

    return $this->getPraticheByOperatoreQueryBuilder($filters, $user)
      ->orderBy('pratica.submissionTime', 'desc')
      ->setFirstResult($offset)
      ->setMaxResults($limit)
      ->getQuery()->execute();
  }

  /**
   * @param $filters
   * @param $user
   * @return \Doctrine\ORM\QueryBuilder
   */
  private function getPraticheByOperatoreQueryBuilder($filters, OperatoreUser $user)
  {
    $qb = $this->createQueryBuilder('pratica');

    $qb->andWhere('pratica.erogatore IN (:erogatore)')
      ->setParameter('erogatore', $user->getEnte()->getErogatori()->toArray());

    $serviziAbilitati = $user->getServiziAbilitati()->toArray();

    if (!empty($filters['servizio']) && in_array($filters['servizio'], $serviziAbilitati)) {
      $qb->andWhere('pratica.servizio = :servizio')
        ->setParameter('servizio', $filters['servizio']);
    }else{
      $qb->andWhere('pratica.servizio IN (:servizio)')
        ->setParameter('servizio', $serviziAbilitati);
    }

    if (!empty($filters['stato'])) {
      $qb->andWhere('pratica.status = :stato')
        ->setParameter('stato', $filters['stato']);
    }else{
      $qb->andWhere('pratica.status >= :stato')
        ->setParameter('stato', self::OPERATORI_LOWER_STATE);
    }

    if ($filters['query_field'] && !empty($filters['query'])) {
      switch ($filters['query_field']) {
        case 1:
//          @todo must must must refactor
//          Non è possibile usare una JOIN in dql!
//          [Semantical Error] Error: Class AppBundle\Entity\User has no field or association named codiceFiscale
//          $qb->andWhere('LOWER(user.codiceFiscale) LIKE LOWER(:searchTerm)')
//            ->leftJoin('pratica.user', 'user')
//            ->setParameter('searchTerm', '%'.$filters['query'].'%');
//          break;

          $userIdList = $this->getEntityManager()->createQueryBuilder()
            ->select('u.id')
            ->from('AppBundle\Entity\CPSUser', 'u')
            ->where("LOWER(u.codiceFiscale) LIKE LOWER(:searchTerm)")
            ->setParameter('searchTerm', '%'.$filters['query'].'%')
            ->getQuery()->getScalarResult();

          if (count($userIdList) > 0) {
            $qb->andWhere('pratica.user IN (:users)')
              ->setParameter('users', $userIdList);
          } else {
            $qb->andWhere('pratica.id IS NULL'); //per annullare la query
          }

          break;
        case 2:
          $qb->andWhere('LOWER(user.nome) LIKE LOWER(:searchTerm) OR LOWER(user.cognome) LIKE LOWER(:searchTerm) OR CONCAT(LOWER(user.nome), \' \', LOWER(user.cognome)) LIKE LOWER(:searchTerm) OR CONCAT(LOWER(user.cognome), \' \', LOWER(user.nome)) LIKE LOWER(:searchTerm)')
            ->leftJoin('pratica.user', 'user')
            ->setParameter('searchTerm', '%'.$filters['query'].'%');
          break;
        case 3:
          $qb->andWhere('pratica.id = :searchTerm');
          $qb->setParameter('searchTerm', $filters['query']);
          break;
      }
    }

    switch ($filters['workflow']) {
      case 'owned':
        $qb->andWhere('pratica.operatore = :operatore')
          ->setParameter('operatore', $user);
        break;
      case 'unassigned':
        $qb->andWhere('pratica.operatore IS NULL');
        break;
    }

    return $qb;
  }

  public function countPraticheByOperatore(OperatoreUser $user, $filters)
  {
    $serviziAbilitati = $user->getServiziAbilitati()->toArray();
    if (empty($serviziAbilitati)){
      return 0;
    }
    return $this->getPraticheByOperatoreQueryBuilder($filters, $user)->select('count(pratica.id)')
      ->getQuery()->getSingleScalarResult();
  }

  /**
   * @return array
   */
  public function getServizioIdListByOperatore(OperatoreUser $user, $minStatus = null)
  {
    $serviziAbilitati = $user->getServiziAbilitati()->toArray();
    if (empty($serviziAbilitati)){
      return [];
    }
    $servizio = 'where servizio_id in (\'' . implode('\',\'', $serviziAbilitati) . '\')';

    $status = '';
    if ($minStatus){
      $status = 'and status >= ' . (int)$minStatus;
    }

    try {
      $stmt = $this->getEntityManager()->getConnection()->prepare(
        "select distinct(servizio_id) from pratica $servizio $status order by servizio_id asc"
      );
      $stmt->execute();

      return $stmt->fetchAll(FetchMode::COLUMN);
    } catch (DBALException $e) {

      return [];
    }
  }

  /**
   * @return array
   */
  public function getStateListByOperatore(OperatoreUser $user, $minStatus = null)
  {
    $serviziAbilitati = $user->getServiziAbilitati()->toArray();
    if (empty($serviziAbilitati)){
      return [];
    }
    $servizio = 'where servizio_id in (\'' . implode('\',\'', $serviziAbilitati) . '\')';

    $status = '';
    if ($minStatus){
      $status = 'and status >= ' . (int)$minStatus;
    }

    try {
      $stmt = $this->getEntityManager()->getConnection()->prepare(
        "select distinct(status) from pratica $servizio $status order by status asc"
      );
      $stmt->execute();

      $stateIdList = $stmt->fetchAll(FetchMode::COLUMN);

      $states = [];
      foreach ($stateIdList as $id) {
        foreach ($this->getClassConstants() as $name => $value) {
          if ($value == $id && strpos($name, 'STATUS_') === 0) {
            $states[] = ['id' => $id, 'name' => $name];
          }
        }
      }

      return $states;

    } catch (DBALException $e) {

      return [];
    }
  }

  public function getMetrics()
  {
    $sql = "SELECT ente.slug as ente, servizio.slug as servizio, pratica.status, count(*) from pratica 
              INNER JOIN ente ON (ente.id = pratica.ente_id) 
              INNER JOIN servizio ON (servizio.id = pratica.servizio_id) 
              GROUP BY ente, servizio, pratica.status 
              ORDER BY servizio ASC, pratica.status DESC ;";

    $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(FetchMode::ASSOCIATIVE);
  }

  private function getClassConstants()
  {
    if (null === $this->classConstants) {
      $class = new \ReflectionClass(Pratica::class);
      $this->classConstants = $class->getConstants();
    }

    return $this->classConstants;
  }

  public function findRecentlySubmittedPraticheByUser(Pratica $pratica, CPSUser $user, $limit)
  {
    $qb = $this->createQueryBuilder('p');
    $qb->where('p.status >= '.Pratica::STATUS_SUBMITTED)
      ->andWhere('p.user = :user')
      ->andWhere('p.id != :pratica')
      ->setParameter('user', $user)
      ->setParameter('pratica', $pratica)
      ->orderBy('p.submissionTime', 'DESC')
      ->setMaxResults($limit);


    return $qb->getQuery()->getResult();
  }
}

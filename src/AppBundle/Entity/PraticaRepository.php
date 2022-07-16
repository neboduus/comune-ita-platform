<?php

namespace AppBundle\Entity;

use AppBundle\Controller\OperatoriController;
use AppBundle\FormIO\SchemaComponent;
use AppBundle\Services\JsonSelect;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use PHPStan\Process\CpuCoreCounter;
use Ramsey\Uuid\Uuid;

/**
 * PraticaRepository
 *
 * This class was generated by the PhpStorm "Php Annotations" Plugin. Add your own custom
 * repository methods below.
 */
class PraticaRepository extends EntityRepository
{
  const OPERATORI_LOWER_STATE = Pratica::STATUS_PAYMENT_PENDING;

  private $classConstants;

  /**
   * @throws \Doctrine\DBAL\Driver\Exception
   * @throws \Doctrine\DBAL\Exception
   */
  public function findRelatedPraticaForUser(CPSUser $user)
  {
    $sql = 'SELECT id from pratica where (related_cfs)::jsonb @> \'"' . $user->getCodiceFiscale() . '"\'';


    $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
    $result = $stmt->executeQuery()->fetchAllAssociative();

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
          Pratica::STATUS_PAYMENT_PENDING,
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
        'latestStatusChangeTimestamp' => 'DESC',
      ]
    )->setMaxResults(10);
  }

  public function findEvidencePraticaForUser(CPSUser $user)
  {
    $timeDiff = "-7 days";
    $qb = $this->createQueryBuilder('p');
    $qb
      ->where('p.user = :user')
      ->andWhere('p.status IN (:statues)')
      ->orWhere('p.latestStatusChangeTimestamp >= :timediff AND p.user = :user')
      ->setParameter('user', $user->getId())
      ->setParameter('timediff', strtotime($timeDiff))
      ->setParameter('statues', [Pratica::STATUS_PAYMENT_PENDING, Pratica::STATUS_DRAFT_FOR_INTEGRATION])
      ->orderBy('p.status', 'ASC')
      ->addOrderBy('p.latestStatusChangeTimestamp', 'DESC')
      ->setMaxResults(10);
    return $qb->getQuery()->getResult();
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

  public function findWithdrawnPraticaForUser(CPSUser $user)
  {
    return $this->findBy(
      [
        'user' => $user,
        'status' => [
          Pratica::STATUS_WITHDRAW,
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
    $qb->where('p.status >= ' . Pratica::STATUS_SUBMITTED)
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
    if (empty($serviziAbilitati)) {
      return [];
    }

    $qb = $this->getPraticheByOperatoreQueryBuilder($filters, $user);
    if (isset($filters['sort']) && isset($filters['order'])) {
      $qb->orderBy('pratica.' . $filters['sort'], strtolower($filters['order']));
    } else {
      $qb->orderBy('pratica.submissionTime', 'desc');
    }
    $qb->addOrderBy('pratica.id', 'desc');

    return $qb->setFirstResult($offset)
      ->setMaxResults($limit)
      ->getQuery()->execute();
  }

  public function countPraticheByOperatore(OperatoreUser $user, $filters)
  {
    $serviziAbilitati = $user->getServiziAbilitati()->toArray();
    if (empty($serviziAbilitati)) {
      return 0;
    }

    $qb = $this->getPraticheByOperatoreQueryBuilder($filters, $user);
    return $qb->select('count(pratica.id)')->getQuery()->getSingleScalarResult();

  }

  public function findStatesPraticheByOperatore(OperatoreUser $user, $filters = [])
  {
    $serviziAbilitati = $user->getServiziAbilitati()->toArray();
    if (empty($serviziAbilitati)) {
      return [];
    }

    $qb = $this->getPraticheByOperatoreQueryBuilder($filters, $user, null, 'DISTINCT pratica.status');
    $qb->addOrderBy('pratica.status', 'asc');

    $result =  $qb->getQuery()->execute();
    $states = [];
    $states[] = ['id' => '', 'name' => 'Tutti'];
    foreach ($result as $s) {
      foreach ($this->getClassConstants() as $name => $value) {
        if ($value == $s['status'] && strpos($name, 'STATUS_') === 0) {
          $states[] = ['id' => $s['status'], 'name' => $name];
        }
      }
    }
    return $states;
  }

  /**
   * @param Pratica $pratica
   * @return \Ramsey\Uuid\UuidInterface|null
   * @throws \Exception
   */
  public function getFolderForApplication(Pratica $pratica)
  {
    $serviceGroup = $pratica->getServizio()->getServiceGroup();
    if (!$serviceGroup) {
      return null;
    }

    $result = $this->createQueryBuilder('pratica')
      ->where('pratica.user = :user')->setParameter('user', $pratica->getUser())
      ->andWhere('pratica.serviceGroup = :group')->setParameter('group', $serviceGroup)
      ->andWhere('pratica.folderId IS NOT NULL')
      ->orderBy('pratica.creationTime', 'DESC')
      ->setFirstResult(0)
      ->setMaxResults(1)
      ->getQuery()->execute();

    return !empty($result) ? $result[0]->getFolderId() : Uuid::uuid4();
  }

  /**
   * @param SchemaComponent[] $fields
   * @param OperatoreUser $user
   * @param $filters
   * @return array|int
   * @see OperatoriController::indexCalculateAction()
   */
  public function getSumFieldsInPraticheByOperatore($fields, OperatoreUser $user, $filters)
  {
    $serviziAbilitati = $user->getServiziAbilitati()->toArray();
    if (empty($serviziAbilitati)) {
      return 'n/a';
    }
    $sqlSelectFields = [];
    $fieldAliases = [];
    foreach ($fields as $index => $field) {
      $formField = "pratica.dematerializedForms " . $field->getName();
      $formFieldAlias = 'df_' . $index;
      $fieldAliases[$formFieldAlias] = $field;
      $sqlSelectFields[] = "SUM(FORMIO_JSON_FIELD($formField, DECIMAL)) as $formFieldAlias";
    }

    $result = array_fill_keys($fields, 0);

    if (!empty($sqlSelectFields)) {
      $data = $this->getPraticheByOperatoreQueryBuilder($filters, $user, FormIO::class)
        ->select($sqlSelectFields)
        ->getQuery()->execute();
      if (isset($data[0])) {
        foreach ($fieldAliases as $alias => $field) {
          if ((int)$data[0][$alias] === 0 && $field->getType() !== 'number') {
            $result[$field->getName()] = 'n/a';
          } else {
            $result[$field->getName()] = number_format($data[0][$alias], 2, ',', '.');
          }
        }
      }
    }

    return $result;
  }

  /**
   * @param SchemaComponent[] $fields
   * @param OperatoreUser $user
   * @param $filters
   * @return array|int
   * @see OperatoriController::indexCalculateAction()
   */
  public function getAvgFieldsInPraticheByOperatore($fields, OperatoreUser $user, $filters)
  {
    $serviziAbilitati = $user->getServiziAbilitati()->toArray();
    if (empty($serviziAbilitati)) {
      return 'n/a';
    }
    $sqlSelectFields = [];
    $fieldAliases = [];
    foreach ($fields as $index => $field) {
      $formField = "pratica.dematerializedForms " . $field->getName();
      $formFieldAlias = 'df_' . $index;
      $fieldAliases[$formFieldAlias] = $field;
      $sqlSelectFields[] = "AVG(FORMIO_JSON_FIELD($formField, DECIMAL)) as $formFieldAlias";
    }

    $result = array_fill_keys($fields, 0);

    if (!empty($sqlSelectFields)) {
      $data = $this->getPraticheByOperatoreQueryBuilder($filters, $user, FormIO::class)
        ->select($sqlSelectFields)
        ->getQuery()->execute();

      if (isset($data[0])) {
        foreach ($fieldAliases as $alias => $field) {
          if ((int)$data[0][$alias] === 0 && $field->getType() !== 'number') {
            $result[$field->getName()] = 'n/a';
          } else {
            $result[$field->getName()] = number_format($data[0][$alias], 2, ',', '.');
          }
        }
      }
    }

    return $result;
  }

  /**
   * @param SchemaComponent[] $fields
   * @param OperatoreUser $user
   * @param $filters
   * @return array|int
   * @see OperatoriController::indexCalculateAction()
   */
  public function getCountNotNullFieldsInPraticheByOperatore($fields, OperatoreUser $user, $filters)
  {
    $serviziAbilitati = $user->getServiziAbilitati()->toArray();
    if (empty($serviziAbilitati)) {
      return 'n/a';
    }
    $sqlSelectFields = [];
    $fieldAliases = [];
    foreach ($fields as $index => $field) {
      $formFieldAlias = 'df_' . $index;
      $fieldAliases[$formFieldAlias] = $field->getName();
    }

    $result = array_fill_keys($fields, 0);

    if (!empty($fieldAliases)) {
      foreach ($fieldAliases as $alias => $field) {
        $data = $this->getPraticheByOperatoreQueryBuilder($filters, $user, FormIO::class)
          ->select("count(FORMIO_JSON_FIELD(pratica.dematerializedForms $field)) as $alias")
          ->andWhere("FORMIO_JSON_FIELD(pratica.dematerializedForms $field) IS NOT NULL")
          ->andWhere("FORMIO_JSON_FIELD(pratica.dematerializedForms $field) != '[]'")
          ->getQuery()->execute();
        if (isset($data[0])) {
          $result[$field] = number_format($data[0][$alias], 0, ',', '.');
        }
      }
    }

    return $result;
  }

  /**
   * @param $filters
   * @param $user
   * @return \Doctrine\ORM\QueryBuilder
   */
  private function getPraticheByOperatoreQueryBuilder($filters, OperatoreUser $user, $entity = null, $fields = 'pratica')
  {
    if (!$entity) {
      $entity = Pratica::class;
    }

    $qb = $this->getEntityManager()->createQueryBuilder()
      ->select($fields)
      ->from($entity, 'pratica')
      ->leftJoin('pratica.servizio', 'servizio');

    // Rimosso per issue #177
    /*$qb->andWhere('pratica.erogatore IN (:erogatore)')
      ->setParameter('erogatore', $user->getEnte()->getErogatori()->toArray());*/

    $serviziAbilitati = $user->getServiziAbilitati()->toArray();

    $qb->andWhere('pratica.servizio IN (:servizio)')
      ->setParameter('servizio', $serviziAbilitati);

    if (!empty($filters['servizio'])) {
      $qb->andWhere('pratica.servizio = :servizio')
        ->setParameter('servizio', $filters['servizio']);
    }

    if (!empty($filters['gruppo'])) {
      $qb->andWhere('pratica.serviceGroup = :group')
        ->setParameter('group', $filters['gruppo']);
    }


    if (!empty($filters['stato'])) {
      $qb->andWhere('pratica.status = :stato')
        ->setParameter('stato', $filters['stato']);
    } else {
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
            ->setParameter('searchTerm', '%' . $filters['query'] . '%')
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
            ->setParameter('searchTerm', '%' . $filters['query'] . '%');
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
        $qb->andWhere('pratica.operatore IS NULL')
           ->andWhere('servizio.workflow = :workflow_forward')
           ->setParameter('workflow_forward', Servizio::WORKFLOW_APPROVAL);
        break;
    }


    if ($filters['collate']) {
      $qb->andWhere('pratica.parent IS NULL');
      $qb->andWhere('pratica.id IN (:grouped)')
        ->setParameter('grouped', $this->getApplicationsCollectionsId($filters['servizio']));
    }

    if (!empty($filters['last_status_change']) && count($filters['last_status_change']) == 2){
      $qb->andWhere('pratica.latestStatusChangeTimestamp >= :start AND pratica.latestStatusChangeTimestamp <= :end');
      $qb->setParameter('start', (int)$filters['last_status_change'][0]);
      $qb->setParameter('end', (int)$filters['last_status_change'][1]);
    }

    return $qb;
  }

  /**
   * @throws \Doctrine\DBAL\Driver\Exception
   * @throws \Doctrine\DBAL\Exception
   */
  private function getApplicationsCollectionsId($filterService)
  {
    $data = [];
    if (empty($filterService)) {
      $dql = 'SELECT json_agg(id) as ids, folder_id FROM pratica GROUP BY folder_id';
    } else {
      $dql = "SELECT json_agg(id) as ids, folder_id FROM pratica WHERE servizio_id = '$filterService' GROUP BY folder_id";
    }

    $stmt = $this->getEntityManager()->getConnection()->prepare($dql);
    $result = $stmt->executeQuery()->fetchAllAssociative();

    foreach ($result as $r) {
      $temp = \json_decode($r['ids']);
      $data[] = $temp[0];
    }

    $dql = 'SELECT id FROM pratica WHERE folder_id IS NULL';
    $stmt = $this->getEntityManager()->getConnection()->prepare($dql);
    $result = $stmt->executeQuery()->fetchAllAssociative();

    foreach ($result as $r) {
      if (!in_array($r['id'], $data)) {
        $data[] = $r['id'];
      }

    }
    return $data;
  }

  public function getApplications($parameters = [], $onlyCount = false, $order = 'creationTime', $sort = 'ASC', $offset = 0, $limit = 10)
  {

    $qb = $this->createQueryBuilder('pratica');
    if (isset($parameters['status'])) {
      $qb->where('pratica.status = :status')->setParameter('status', $parameters['status']);
    } else {
      $qb->where('pratica.status != :status')->setParameter('status', Pratica::STATUS_DRAFT);
    }

    if (isset($parameters['service'])) {
      $qb->andWhere('pratica.servizio IN (:services)')->setParameter('services', $parameters['service']);
    }

    // after|before|strictly_after|strictly_before
    if (isset($parameters['createdAt'])) {
      if (isset($parameters['createdAt']['strictly_after'])) {
        $qb->andWhere('pratica.createdAt > :createdAt')->setParameter('createdAt', $parameters['createdAt']);
      } else if (isset($parameters['createdAt']['after'])) {
        $qb->andWhere('pratica.createdAt >= :createdAt')->setParameter('createdAt', $parameters['createdAt']);
      }

      if (isset($parameters['createdAt']['strictly_before'])) {
        $qb->andWhere('pratica.createdAt < :createdAt')->setParameter('createdAt', $parameters['createdAt']);
      } else if (isset($parameters['createdAt']['before'])) {
        $qb->andWhere('pratica.createdAt <= :createdAt')->setParameter('createdAt', $parameters['createdAt']);
      }
    }

    if (isset($parameters['updatedAt'])) {
      if (isset($parameters['updatedAt']['strictly_after'])) {
        $qb->andWhere('pratica.updatedAt > :updatedAt')->setParameter('updatedAt', $parameters['updatedAt']);
      } else if (isset($parameters['updatedAt']['after'])) {
        $qb->andWhere('pratica.updatedAt >= :updatedAt')->setParameter('updatedAt', $parameters['updatedAt']);
      }

      if (isset($parameters['updatedAt']['strictly_before'])) {
        $qb->andWhere('pratica.updatedAt < :updatedAt')->setParameter('updatedAt', $parameters['updatedAt']);
      } else if (isset($parameters['updatedAt']['before'])) {
        $qb->andWhere('pratica.updatedAt <= :updatedAt')->setParameter('updatedAt', $parameters['updatedAt']);
      }
    }
    // after|before|strictly_after|strictly_before
    if (isset($parameters['submittedAt'])) {
      if (isset($parameters['submittedAt']['strictly_after'])) {
        $qb->andWhere('pratica.submissionTime > :submittedAt')->setParameter('submittedAt', (new \DateTime($parameters['submittedAt']['strictly_after']))->getTimestamp());
      } else if (isset($parameters['submittedAt']['after'])) {
        $qb->andWhere('pratica.submissionTime >= :submittedAt')->setParameter('submittedAt', (new \DateTime($parameters['submittedAt']['after']))->getTimestamp());
      }

      if (isset($parameters['submittedAt']['strictly_before'])) {
        $qb->andWhere('pratica.submissionTime < :submittedAt')->setParameter('submittedAt', (new \DateTime($parameters['submittedAt']['strictly_before']))->getTimestamp());
      } else if (isset($parameters['submittedAt']['before'])) {
        $qb->andWhere('pratica.submissionTime <= :submittedAt')->setParameter('submittedAt', (new \DateTime($parameters['submittedAt']['before']))->getTimestamp());
      }
    }

    if (isset($parameters['user'])) {
      $qb->andWhere('pratica.user = :user')->setParameter('user', $parameters['user']);
    }

    if ($onlyCount) {
      $qb->select('COUNT(pratica.id)');
      return $qb->getQuery()->getSingleScalarResult();
    } else {
      $qb
        ->orderBy('pratica.' . $order, $sort)
        ->setFirstResult($offset)
        ->setMaxResults($limit);
    }

    return $qb->getQuery()->execute();
  }

  /**
   * @param Pratica $pratica
   * @return Pratica[]
   */
  public function getApplicationsInFolder(Pratica $pratica)
  {
    $serviceGroup = $pratica->getServizio()->getServiceGroup();
    $folderId = $pratica->getFolderId();
    $applications = [];
    if ($serviceGroup && $folderId){
      $applications = $this->createQueryBuilder('pratica')
        ->where('pratica.user = :user')->setParameter('user', $pratica->getUser())
        ->andWhere('pratica.status >= :status')->setParameter('status', Pratica::STATUS_PRE_SUBMIT)
        ->andWhere('pratica.folderId = :folderId')->setParameter('folderId', $folderId)
        ->andWhere('pratica.parent IS NULL')
        ->orderBy('pratica.submissionTime', 'DESC')
        ->getQuery()->execute();
    }
    if (count($applications) == 0 && $pratica->getParent() !== null ){
      $applications = $this->getApplicationsInFolder($pratica->getRootParent());
    }

    if ( count($applications) == 0 && $pratica->getChildren()->count() > 0 ) {
      $applications[]=$pratica;
    }
    return $applications;
  }

  /**
   * @return array
   */
  public function getServizioIdListByOperatore(OperatoreUser $user, $minStatus = null)
  {
    $serviziAbilitati = $user->getServiziAbilitati()->toArray();
    if (empty($serviziAbilitati)) {
      return [];
    }
    $servizio = 'where servizio_id in (\'' . implode('\',\'', $serviziAbilitati) . '\')';

    $status = '';
    if ($minStatus) {
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
    if (empty($serviziAbilitati)) {
      return [];
    }
    $servizio = 'where servizio_id in (\'' . implode('\',\'', $serviziAbilitati) . '\')';

    $status = '';
    if ($minStatus) {
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
    $sql = "SELECT ente.slug as ente, servizio.slug as servizio, pratica.status, service_group.slug as gruppo, categoria.slug as categoria, count(*) from pratica
              INNER JOIN ente ON (ente.id = pratica.ente_id)
              INNER JOIN servizio ON (servizio.id = pratica.servizio_id)
              LEFT JOIN service_group ON (service_group.id = pratica.service_group_id)
              LEFT JOIN categoria ON (categoria.id = servizio.topics)
              GROUP BY ente, servizio, gruppo, categoria, pratica.status
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
    $qb->where('p.status >= ' . Pratica::STATUS_SUBMITTED)
      ->andWhere('p.user = :user')
      ->setParameter('user', $user)
      ->andWhere('p.id NOT IN (:tree)')
      ->setParameter('tree', $pratica->getTreeIdList())
      ->orderBy('p.submissionTime', 'DESC')
      ->setMaxResults($limit);

    return $qb->getQuery()->getResult();
  }

  public function findPraticheByUser(CPSUser $user, $filters, $limit, $offset)
  {
    $qb = $this->getPraticheByUserQueryBuilder($filters, $user);
    if (isset($filters['sort']) && isset($filters['order'])) {
      $qb->orderBy('pratica.' . $filters['sort'], strtolower($filters['order']));
    } else {
      $qb->orderBy('pratica.submissionTime', 'desc');
    }
    $qb->addOrderBy('pratica.id', 'desc');

    return $qb->setFirstResult($offset)
      ->setMaxResults($limit)
      ->getQuery()->execute();
  }

  public function countPraticheByUser(CPSUser $user, $filters)
  {
    return $this->getPraticheByUserQueryBuilder($filters, $user)->select('count(pratica.id)')
      ->getQuery()->getSingleScalarResult();
  }

  private function getPraticheByUserQueryBuilder($filters, CPSUser $user)
  {
    if (!empty($filters['data'])) {
      $entity = FormIO::class;
    } else {
      $entity = Pratica::class;
    }

    $qb = $this->getEntityManager()->createQueryBuilder()
      ->select('pratica')
      ->from($entity, 'pratica');

    if (!empty($filters['status'])) {
      $qb->andWhere('pratica.status IN (:status)')
        ->setParameter('status', (array)$filters['status']);
    }

    $qb->andWhere('pratica.user = :user')
      ->setParameter('user', $user);

    if (!empty($filters['service'])) {
      $qb->andWhere('servizio.slug in (:service)')
        ->leftJoin('pratica.servizio', 'servizio')
        ->setParameter('service', (array)$filters['service']);
    }

    if (!empty($filters['data'])) {
      foreach ($filters['data'] as $field => $value) {
        $fieldValueKey = str_replace('.', '', $field);
        $qb->andWhere("LOWER(FORMIO_JSON_FIELD(pratica.dematerializedForms $field)) = :{$fieldValueKey}")
          ->setParameter($fieldValueKey, strtolower($value));
      }
    }

    return $qb;
  }

  /**
   * @param $filters
   * @param Pratica $pratica
   * @return float|int|mixed|string
   */
  public function getMessages($filters, Pratica $pratica)
  {
    $qb = $this->getEntityManager()->createQueryBuilder()
      ->select('message')
      ->from('AppBundle:Message', 'message')
      ->where('message.application = :application')
      ->setParameter('application', $pratica);

    if (!empty($filters['visibility'])) {
      $qb->andWhere('message.visibility = :visibility')
        ->setParameter('visibility', (array)$filters['visibility']);
    }

    if (isset($filters['from_date'])) {
      $qb->andWhere('message.createdAt >= :from_date')->setParameter('from_date', $filters['from_date']->getTimestamp());
    }

    if (isset($filters['to_date'])) {
      $qb->andWhere('message.createdAt <= :to_date')->setParameter('to_date', $filters['to_date']->getTimestamp());
    }

    $qb->orderBy('message.createdAt', 'ASC');

    return $qb->getQuery()->getResult();
  }

  /**
   * @param Pratica $pratica
   * @return void
   */
  public function getLastMessageByApplicationOwner(Pratica $pratica)
  {
    $qb = $this->getEntityManager()->createQueryBuilder()
      ->select('message')
      ->from('AppBundle:Message', 'message')
      ->where('message.application = :application')
      ->setParameter('application', $pratica)
      ->andWhere('message.author = :user')
      ->setParameter('user', $pratica->getUser())
      ->orderBy('message.createdAt', 'DESC')
      ->setMaxResults(1);

    try {
      return $qb->getQuery()->getSingleResult();
    } catch (NoResultException $e) {
    } catch (NonUniqueResultException $e) {
      return null;
    }
  }


  public function getMessageAttachments($filters, Pratica $pratica)
  {
    $qb = $this->getEntityManager()->createQueryBuilder()
      ->select('attachment')
      ->from('AppBundle:AllegatoMessaggio', 'attachment')
      ->join('attachment.messages', 'message');

    if (!empty($filters['visibility'])) {
      $qb->andWhere('message.visibility = :visibility')
        ->setParameter('visibility', (array)$filters['visibility']);
    }

    if (!empty($filters['author'])) {
      $qb->andWhere('message.author = :author')
        ->setParameter('author', (array)$filters['author']);
    }

    $qb->andWhere('message.application = :application')
      ->setParameter('application', $pratica)
      ->orderBy('message.createdAt', 'asc');


    return $qb->getQuery()->getResult();
  }

  public function findOrderedMeetings(Pratica $pratica) {
    $qb = $this->getEntityManager()->createQueryBuilder()
      ->select('meeting')
      ->from('AppBundle:Meeting', 'meeting')
      ->where('meeting.id IN (:meetings)')
      ->setParameter(':meetings', $pratica->getMeetings())
    ->orderBy('meeting.fromTime', 'desc');

    return $qb->getQuery()->getResult();
  }

  public function findIncomingMeetings(Pratica $pratica) {
    $qb = $this->getEntityManager()->createQueryBuilder()
      ->select('meeting')
      ->from('AppBundle:Meeting', 'meeting')
      ->where('meeting.id IN (:meetings)')
      ->andWhere('meeting.fromTime >= :now')
      ->andWhere('meeting.status IN (:statuses)')
      ->setParameter(':meetings', $pratica->getMeetings())
      ->setParameter(':statuses', [Meeting::STATUS_APPROVED, Meeting::STATUS_PENDING])
      ->setParameter(':now', new \DateTime())
      ->orderBy('meeting.fromTime', 'asc');

    return $qb->getQuery()->getResult();
  }

}

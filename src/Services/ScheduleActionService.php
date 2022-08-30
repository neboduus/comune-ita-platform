<?php

namespace App\Services;

use App\Entity\ScheduledAction;
use App\ScheduledAction\Exception\AlreadyScheduledException;
use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;

class ScheduleActionService
{
  /**
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * @var EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var EntityRepository
   */
  protected $entityRepository;

  public function __construct(
    EntityManagerInterface $entityManager,
    LoggerInterface        $logger
  )
  {
    $this->entityManager = $entityManager;
    $this->entityRepository = $this->entityManager->getRepository('App\Entity\ScheduledAction');
    $this->logger = $logger;
  }

  /**
   * @param string $service
   * @param string $type
   * @param string $params
   *
   * @return ScheduledAction
   * @throws AlreadyScheduledException
   */
  public function appendAction($service, $type, $params)
  {
    if ($this->entityRepository->findBy([
      'service' => $service,
      'type' => $type,
      'params' => $params,
      'status' => ScheduledAction::STATUS_PENDING
    ])
    ) {
      throw new AlreadyScheduledException();
    }

    $scheduled = (new ScheduledAction())
      ->setService($service)
      ->setType($type)
      ->setParams($params);
    $this->entityManager->persist($scheduled);
    $this->entityManager->flush();

    return $scheduled;
  }

  public function markAsDone(ScheduledAction $action)
  {
    $action->setDone();
    $this->entityManager->flush($action);
  }

  public function markAsInvalid(ScheduledAction $action)
  {
    $action->setInvalid();
    $this->entityManager->flush($action);
  }

  public function removeHostAndSaveLog(ScheduledAction $action, $message)
  {
    $action->setHostname(null);
    $action->setLog($message);
    $action->incRetry();
    $this->entityManager->persist($action);
    $this->entityManager->flush($action);
  }

  /**
   * @param string $hostname
   * @return \App\Entity\ScheduledAction[]
   */
  public function getPendingActions($hostname)
  {
    return $this->entityRepository->findBy([
      'hostname' => $hostname,
      'status' => ScheduledAction::STATUS_PENDING,
    ], ['updatedAt' => 'DESC']);
  }

  /**
   * @param \DateTime $date
   * @return mixed
   * @throws \Exception
   */
  public function getOldReservedActions(\DateTime $date)
  {
    $status = ScheduledAction::STATUS_PENDING;

    $qb = $this->entityManager->createQueryBuilder();
    return $qb->select('t')
      ->from('App:ScheduledAction', 't')
      ->where($qb->expr()->isNotNull('t.hostname'))
      ->andWhere('t.status = :status')
      ->setParameter('status', $status)
      ->andWhere('t.updatedAt < :date')
      ->setParameter('date', $date)
      ->getQuery()
      ->getResult();
  }

  /**
   * @param $hostname
   * @param $count
   * @param $minutes
   * @param $maxRetry
   * @return int
   * @throws \Doctrine\DBAL\Exception
   */
  public function reserveActions($hostname, $count, $minutes, $maxRetry)
  {
    $status = ScheduledAction::STATUS_PENDING;
    $date = new \DateTime('now', new \DateTimeZone(date_default_timezone_get()));
    $oneHourOlder = new \DateTime('-'. $minutes . ' minutes', new \DateTimeZone(date_default_timezone_get()));

    /*$dql = 'UPDATE scheduled_action SET hostname = ?, updated_at = ?
            WHERE id IN (SELECT id FROM scheduled_action WHERE (hostname IS NULL OR updated_at < ?) AND status = ? and (retry IS NULL OR retry < ?) ORDER BY updated_at ASC LIMIT ?)';

    select id, updated_at,
    (power(2, coalesce(retry, 0)) * interval '10 minutes') as power,
    (power(coalesce(retry, 0), 2) * interval '10 minutes') as power_reverte,
    (log(2,coalesce(retry, 0) + 1) * retry  * interval '10 minutes') as log2_retry,
    (log(1.7,coalesce(retry, 0) + 1)  * interval '10 minutes') as log2,
    (log(2,coalesce(retry, 0) + 1)  * interval '10 minutes') as log2,
    (ln(coalesce(retry, 0) + 1)  * interval '10 minutes') as ln,
    (log(coalesce(retry, 0) + 1)  * interval '10 minutes') as log10,
    (exp(coalesce(retry, 0))  * interval '10 minutes') as exp,
    retry
    from scheduled_action where status = 1
    order by retry asc

    select id, updated_at,
    ( updated_at::timestamp + (power(coalesce(retry, 0), 2) * interval '10 minutes')) as next_execution,
    (power(coalesce(retry, 0), 2) * interval '10 minutes') as power_reverte,
    retry
    from scheduled_action where status = 1
    order by retry asc
    */

    $dql = "UPDATE scheduled_action SET hostname = ?, updated_at = ?
              WHERE id IN (
                  SELECT id FROM scheduled_action
                  WHERE ( hostname IS NULL OR ( created_at::timestamp + (power(2, coalesce(retry, 0)) * interval '{$minutes} minutes') < ? ) )
                    AND status = ?
                    AND (retry IS NULL OR retry < ?)
                  ORDER BY updated_at ASC LIMIT ?
              )";

    return $this->entityManager->getConnection()->executeStatement($dql, [
      $hostname,
      $date->format('Y-m-d H:i:s'),
      //$minutes,
      $oneHourOlder->format('Y-m-d H:i:s'),
      (int)$status,
      (int)$maxRetry,
      (int)$count
    ]);

  }

  /**
   * @return array
   * @throws \Doctrine\DBAL\DBALException
   */
  public function getStatistic()
  {
    $status = ScheduledAction::STATUS_PENDING;
    $sql = 'SELECT hostname, COUNT(*) AS count FROM scheduled_action WHERE status = ' . (int)$status . ' GROUP BY hostname';

    $stmt = $this->entityManager->getConnection()->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(FetchMode::ASSOCIATIVE);
  }
}

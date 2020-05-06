<?php

namespace AppBundle\Services;

use AppBundle\Entity\ScheduledAction;
use AppBundle\ScheduledAction\Exception\AlreadyScheduledException;
use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;

class ScheduleActionService
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var EntityRepository
     */
    protected $entityRepository;

    public function __construct(
        EntityManager $entityManager,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->entityRepository = $this->entityManager->getRepository('AppBundle:ScheduledAction');
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

    /**
     * @param string $hostname
     * @return \AppBundle\Entity\ScheduledAction[]
     */
    public function getPendingActions($hostname)
    {
      return $this->entityRepository->findBy([
        'hostname' => $hostname,
        'status' => ScheduledAction::STATUS_PENDING,
      ], ['createdAt' => 'ASC']);
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
        ->from('AppBundle:ScheduledAction', 't')
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
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function reserveActions($hostname, $count, $minutes)
    {
      $status = ScheduledAction::STATUS_PENDING;
      $date = new \DateTime('now', new \DateTimeZone(date_default_timezone_get()));
      $oneHourOlder = new \DateTime('-'. $minutes . ' minutes', new \DateTimeZone(date_default_timezone_get()));

      $dql = 'UPDATE scheduled_action SET hostname = ?, updated_at = ?
              WHERE id IN (SELECT id FROM scheduled_action WHERE (hostname IS NULL OR updated_at < ?) AND status = ? ORDER BY created_at ASC LIMIT ?)';

      return $this->entityManager->getConnection()->executeUpdate($dql, [
        $hostname,
        $date->format('Y-m-d H:i:s'),
        $oneHourOlder->format('Y-m-d H:i:s'),
        (int)$status,
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

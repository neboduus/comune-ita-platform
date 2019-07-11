<?php

namespace AppBundle\Services;

use AppBundle\Entity\ScheduledAction;
use AppBundle\ScheduledAction\Exception\AlreadyScheduledException;
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
        $this->entityManager->remove($action); //TODO: usare un campo separato?
    }

    public function markAsInvalid(ScheduledAction $action)
    {
        $this->entityManager->remove($action); //TODO: usare un campo separato?
    }

    public function done($entity = null)
    {
        $this->entityManager->flush($entity);
    }

    /**
     * @return \AppBundle\Entity\ScheduledAction[]
     */
    public function getActions()
    {
        return $this->entityRepository->findBy([], ['createdAt' => 'ASC']);
    }
}

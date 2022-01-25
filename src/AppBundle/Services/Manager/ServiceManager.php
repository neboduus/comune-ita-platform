<?php

namespace AppBundle\Services\Manager;

use AppBundle\Entity\Servizio;
use AppBundle\Event\KafkaEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ServiceManager
{

  /**
   * @var EntityManagerInterface
   */
  private $entityManager;

  /**
   * @var EventDispatcherInterface
   */
  private $dispatcher;

  /**
   * CategoryManager constructor.
   * @param EntityManagerInterface $entityManager
   * @param EventDispatcherInterface $dispatcher
   */
  public function __construct(EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher)
  {
    $this->entityManager = $entityManager;
    $this->dispatcher = $dispatcher;
  }

  /**
   * @param Servizio $servizio
   */
  public function save(Servizio $servizio)
  {
    $this->entityManager->persist($servizio);
    $this->entityManager->flush();

    $this->dispatcher->dispatch(KafkaEvent::NAME, new KafkaEvent($servizio));
  }

}

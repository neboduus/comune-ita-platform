<?php

namespace AppBundle\Services\Manager;

use AppBundle\Entity\Calendar;
use AppBundle\Event\KafkaEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CalendarManager
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
   * @param Calendar $calendar
   */
  public function save(Calendar $calendar)
  {
    $this->entityManager->persist($calendar);
    $this->entityManager->flush();

    $this->dispatcher->dispatch(KafkaEvent::NAME, new KafkaEvent($calendar));
  }


}

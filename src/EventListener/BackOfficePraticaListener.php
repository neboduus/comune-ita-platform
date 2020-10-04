<?php

namespace App\EventListener;

use App\BackOffice\BackOfficeInterface;
use App\Entity\DematerializedFormPratica;
use App\Event\PraticaOnChangeStatusEvent;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Psr\Log\LoggerInterface;

class BackOfficePraticaListener
{

  /**
   * @var Container
   */
  private $container;

  /**
   * @var LoggerInterface
   */
  private $logger;

  public function __construct(Container $container,  LoggerInterface $logger)
  {
    $this->container = $container;
    $this->logger = $logger;
  }

  public function onStatusChange(PraticaOnChangeStatusEvent $event)
  {
    $pratica = $event->getPratica();
    $service = $pratica->getServizio();
    $integrations = $service->getIntegrations();

    if (!empty($integrations) && array_key_exists($event->getNewStateIdentifier(), $integrations) && $pratica instanceof DematerializedFormPratica) {

      /** @var BackOfficeInterface $backOfficeHandler */
      $backOfficeHandler = $this->container->get($integrations[$event->getNewStateIdentifier()]);
      $backOfficeHandler->execute($pratica);
    }
  }
}

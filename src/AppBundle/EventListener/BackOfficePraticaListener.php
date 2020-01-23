<?php

namespace AppBundle\EventListener;

use AppBundle\BackOffice\BackOfficeInterface;
use AppBundle\Entity\DematerializedFormPratica;
use AppBundle\Event\PraticaOnChangeStatusEvent;
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
      $data = $pratica->getDematerializedForms();
      unset($data['flattened']['submit']);

      /** @var BackOfficeInterface $backOfficeHandler */
      $backOfficeHandler = $this->container->get($integrations[$event->getNewStateIdentifier()]);
      $backOfficeHandler->execute($data['flattened']);
    }
  }
}

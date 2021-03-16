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

  /**
   * @param PraticaOnChangeStatusEvent $event
   * @throws \Exception
   */
  public function onStatusChange(PraticaOnChangeStatusEvent $event)
  {
    $pratica = $event->getPratica();
    $service = $pratica->getServizio();
    $integrations = $service->getIntegrations();

    if (!empty($integrations) && $pratica instanceof DematerializedFormPratica) {

      foreach ($integrations as $integration) {
        /** @var BackOfficeInterface $backOfficeHandler */
        $backOfficeHandler = $this->container->get($integration);
        $result = $backOfficeHandler->execute($pratica);
        if (is_array($result) && isset($result["error"])) {
          throw new \Exception($result["error"]);
        }
      }
    }
  }
}

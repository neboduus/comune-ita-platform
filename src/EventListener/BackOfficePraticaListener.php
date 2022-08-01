<?php

namespace App\EventListener;

use App\BackOffice\BackOfficeInterface;
use App\Entity\DematerializedFormPratica;
use App\Event\PraticaOnChangeStatusEvent;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class BackOfficePraticaListener
{

  /**
   * @var Container
   */
  private $container;

  /**
   * @var SessionInterface
   */
  private $session;

  /**
   * @var LoggerInterface
   */
  private $logger;

  public function __construct(Container $container, LoggerInterface $logger, SessionInterface $session)
  {
    $this->container = $container;
    $this->logger = $logger;
    $this->session = $session;
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
          $this->session->getFlashBag()->add(
            'error',
            $result["error"]
          );
          $this->logger->error($result["error"] . " for pratica " . $pratica->getId());
          throw new \Exception($result["error"]);
        }
      }
    }
  }
}

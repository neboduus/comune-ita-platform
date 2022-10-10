<?php

namespace App\Handlers\Servizio;

use App\Entity\Ente;
use App\Entity\Servizio;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ImisHandler extends AbstractServizioHandler
{

  /**
   * ImisHandler constructor.
   * @param TokenStorageInterface $tokenStorage
   * @param LoggerInterface $logger
   * @param UrlGeneratorInterface $router
   */
  public function __construct(TokenStorageInterface $tokenStorage, LoggerInterface $logger, UrlGeneratorInterface $router)
  {
    parent::__construct($tokenStorage, $logger, $router);
    $this->setCallToActionText('servizio.imis.download_pdf_imis');
  }

  public function getErrorMessage()
  {
    return "Non é possibile effettuare il download del file. Non risultano immobili a suo nome all'interno del comune.";
  }

  /**
   * @param Servizio $servizio
   * @return Response
   * @deprecated since 2.0.0
   * @throws \Exception
   */
  public function execute(Servizio $servizio): Response
  {
    throw new \Exception("Questa modalità di servizio non è più supportata e verrà presto rimossa.");
  }
}

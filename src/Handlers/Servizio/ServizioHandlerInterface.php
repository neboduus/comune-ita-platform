<?php

namespace App\Handlers\Servizio;

use App\Entity\Ente;
use App\Entity\Servizio;
use Symfony\Component\HttpFoundation\Response;

interface ServizioHandlerInterface
{
  /**
   * @param Servizio $servizio
   * @param Ente $ente
   * @throws ForbiddenAccessException
   */
  public function canAccess(Servizio $servizio, Ente $ente);

  /**
   * @param Servizio $servizio
   * @param Ente $ente
   * @return Response
   * @throws \Exception
   */
  public function execute(Servizio $servizio, Ente $ente);

  public function getCallToActionText();

  public function getErrorMessage();
}

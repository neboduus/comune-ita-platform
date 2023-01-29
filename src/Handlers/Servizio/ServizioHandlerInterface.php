<?php

namespace App\Handlers\Servizio;

use App\Entity\Servizio;
use Symfony\Component\HttpFoundation\Response;

interface ServizioHandlerInterface
{
  /**
   * @param Servizio $servizio
   * @throws ForbiddenAccessException
   */
  public function canAccess(Servizio $servizio);

  /**
   * @param Servizio $servizio
   * @return Response
   * @throws \Exception
   */
  public function execute(Servizio $servizio);

  public function getCallToActionText();

  public function getErrorMessage();
}

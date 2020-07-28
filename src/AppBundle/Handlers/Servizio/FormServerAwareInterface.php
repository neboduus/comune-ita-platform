<?php

namespace AppBundle\Handlers\Servizio;

use AppBundle\Entity\Pratica;

interface FormServerAwareInterface
{
  public function getFormServerUrl();

  public function getFormServerUrlForPratica(Pratica $pratica);
}

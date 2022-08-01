<?php

namespace App\Protocollo;

use App\Entity\AllegatoInterface;
use App\Entity\Pratica;
use Symfony\Component\Validator\Constraints\All;

interface PredisposedProtocolHandlerInterface
{

  /**
   * @param Pratica $pratica
   * @return mixed
   */
  public function protocolPredisposed(Pratica $pratica);

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $attachment
   * @return mixed
   */
  public function protocolPredisposedAttachment(Pratica $pratica, AllegatoInterface $attachment);

}

<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RispostaIntegrazioneRepository")
 */
class RispostaIntegrazione extends Allegato
{
  const STATUS_PENDING = 1000;
  const STATUS_DONE = 2000;
  const TYPE_DEFAULT = 'risposta_integrazione';

  /**
   * @ORM\Column(type="json_array", options={"jsonb":true})
   * @var \JsonSerializable
   */
  private $payload;

  /**
   * @ORM\Column(type="integer")
   * @var integer
   */
  private $status;

  public function __construct()
  {
    parent::__construct();
    $this->type = self::TYPE_DEFAULT;
    $this->status = self::STATUS_PENDING;
  }

  /**
   * @return mixed
   */
  public function getPayload()
  {
    return $this->payload;
  }

  /**
   * @param string $payload
   *
   * @return RispostaIntegrazione
   */
  public function setPayload($payload)
  {
    $this->payload = $payload;

    return $this;
  }

  /**
   * @return mixed
   */
  public function getIdRichiestaIntegrazione()
  {
    $payload = $this->payload;

    return $payload['richiesta_integrazione'];
  }

  /**
   * @param $idRichiestaIntegrazione
   */
  public function setIdRichiestaIntegrazione($idRichiestaIntegrazione): void
  {
    $this->payload = ['richiesta_integrazione' => $idRichiestaIntegrazione];
  }

  /**
   * @return mixed
   */
  public function getStatus()
  {
    return $this->status;
  }

  /**
   * @param mixed $status
   *
   * @return RispostaIntegrazione
   */
  public function setStatus($status)
  {
    $this->status = $status;

    return $this;
  }

  /**
   * @return RispostaIntegrazione
   */
  public function markAsDone()
  {
    return $this->setStatus(self::STATUS_DONE);
  }

  /**
   * @return string
   */
  public function getType(): string
  {
    return self::TYPE_DEFAULT;
  }
}

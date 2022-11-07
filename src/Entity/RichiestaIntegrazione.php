<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class RichiestaIntegrazione extends Allegato
{
  const STATUS_PENDING = 1000;
  const STATUS_DONE = 2000;
  const TYPE_DEFAULT = 'richiesta_integrazione';
  const PAYLOAD_ATTACHMENTS = 'attachments';

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\Pratica", inversedBy="richiesteIntegrazione")
   * @var Pratica $praticaPerCuiServeIntegrazione
   */
  private $praticaPerCuiServeIntegrazione;

  /**
   * @ORM\Column(type="integer")
   * @var integer
   */
  private $status;

  /**
   * @ORM\Column(type="array", nullable=true)
   * @var ArrayCollection
   */
  private $numeriProtocollo;


  public function __construct()
  {
    parent::__construct();
    $this->type = self::TYPE_DEFAULT;
    $this->status = self::STATUS_PENDING;
    $this->numeriProtocollo = new ArrayCollection();
  }

  /**
   * @return Pratica|null
   */
  public function getPratica()
  {
    return $this->praticaPerCuiServeIntegrazione;
  }

  /**
   * @param Pratica $pratica
   *
   * @return $this
   */
  public function setPratica(Pratica $pratica)
  {
    $this->praticaPerCuiServeIntegrazione = $pratica;

    return $this;
  }

  /**
   * @return mixed
   */
  public function getAttachments()
  {
    if (isset($this->payload[self::PAYLOAD_ATTACHMENTS])) {
      return $this->payload[self::PAYLOAD_ATTACHMENTS];
    }
    return [];
  }

  /**
   * @param array $attachments
   * @return void
   */
  public function setAttachments(array $attachments): void
  {
    $payload = $this->payload;
    $payload[self::PAYLOAD_ATTACHMENTS] = $attachments;
    $this->payload = $payload;
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
   * @return RichiestaIntegrazione
   */
  public function setStatus($status)
  {
    $this->status = $status;

    return $this;
  }

  /**
   * @return RichiestaIntegrazione
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

  /**
   * @param array $numeroDiProtocollo
   *
   * @return RichiestaIntegrazione
   */
  public function addNumeroDiProtocollo($numeroDiProtocollo)
  {
    if (!$this->numeriProtocollo->contains($numeroDiProtocollo)) {
      $this->numeriProtocollo->add($numeroDiProtocollo);
    }

    return $this;
  }

  /**
   * @ORM\PreFlush()
   */
  public function arrayToJson()
  {
    $this->numeriProtocollo = json_encode($this->getNumeriProtocollo()->toArray());
  }

  /**
   * @return mixed
   */
  public function getNumeriProtocollo()
  {
    $this->jsonToArray();
    return $this->numeriProtocollo;
  }

  /**
   * @ORM\PostLoad()
   * @ORM\PostUpdate()
   */
  public function jsonToArray()
  {
    if ($this->numeriProtocollo == null) {
      $this->numeriProtocollo = new ArrayCollection();
    } elseif (!$this->numeriProtocollo instanceof ArrayCollection) {
      $this->numeriProtocollo = new ArrayCollection(json_decode($this->numeriProtocollo));
    }
  }
}

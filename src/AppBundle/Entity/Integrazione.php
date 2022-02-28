<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Integrazione
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="AppBundle\Entity\IntegrazioneRepository")
 */
class Integrazione extends Allegato
{
  const TYPE_DEFAULT = 'integrazione';

  /**
   * @ORM\Column(type="array", nullable=true)
   * @var ArrayCollection
   */
  private $numeriProtocollo;

  /**
   * ModuloCompilato constructor.
   */
  public function __construct()
  {
    parent::__construct();
    $this->type = self::TYPE_DEFAULT;
    $this->numeriProtocollo = new ArrayCollection();
  }

  /**
   * @param array $numeroDiProtocollo
   *
   * @return Integrazione
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
    if (!$this->numeriProtocollo instanceof ArrayCollection) {
      $this->jsonToArray();
    }

    return $this->numeriProtocollo;
  }

  /**
   * @ORM\PostLoad()
   * @ORM\PostUpdate()
   */
  public function jsonToArray()
  {
    $this->numeriProtocollo = new ArrayCollection(json_decode($this->numeriProtocollo));
  }

  /**
   * @return \JsonSerializable
   */
  public function getIdRichiestaIntegrazione()
  {
    $payload = $this->payload;
    return $payload[RichiestaIntegrazione::TYPE_DEFAULT];
  }

  /**
   * @param $idRichiestaIntegrazione
   */
  public function setIdRichiestaIntegrazione($idRichiestaIntegrazione): void
  {
    $this->payload = [RichiestaIntegrazione::TYPE_DEFAULT => $idRichiestaIntegrazione];
  }


  public function getType(): string
  {
    return self::TYPE_DEFAULT;
  }


}

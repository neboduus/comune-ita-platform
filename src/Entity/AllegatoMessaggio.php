<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class AllegatoMessaggio
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class AllegatoMessaggio extends Allegato
{
  const TYPE_DEFAULT = 'messaggio';

  /**
   * @ORM\ManyToMany(targetEntity="App\Entity\Message", mappedBy="attachments")
   * @var ArrayCollection
   */
  private $messages;

  /**
   * @ORM\Column(type="array", nullable=true)
   * @var ArrayCollection
   */
  private $numeriProtocollo;

  /**
   * Allegato Messaggio constructor.
   */
  public function __construct()
  {
    parent::__construct();
    $this->type = self::TYPE_DEFAULT;
    $this->messages = new ArrayCollection();
    $this->numeriProtocollo = new ArrayCollection();
  }

  public function getType(): string
  {
    return self::TYPE_DEFAULT;
  }

  /**
   * @return ArrayCollection
   */
  public function getMessages(): Collection
  {
    return $this->messages;
  }

  /**
   * @param Message $message
   * @return $this
   */
  public function addMessage(Message $message)
  {
    if (!$this->messages->contains($message)) {
      $this->messages->add($message);
    }

    return $this;
  }

  /**
   * @param Message $message
   * @return $this
   */
  public function removeMessage(Message $message)
  {
    if ($this->messages->contains($message)) {
      $this->messages->removeElement($message);
    }

    return $this;
  }


  /**
   * @param array $numeroDiProtocollo
   *
   * @return AllegatoMessaggio
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

  public function getPratiche(): Collection
  {
    $pratiche = new ArrayCollection();
    foreach ($this->messages as $message) {
      /** @var Message $message */

      if (!$pratiche->contains($message->getApplication())) {
        $pratiche->add($message->getApplication());
      }
    }
    return $pratiche;
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
}

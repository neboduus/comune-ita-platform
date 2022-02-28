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
  const PAYLOAD_MESSAGES = 'messages';
  const PAYLOAD_ATTACHMENTS = 'attachments';

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
    $payload = $this->payload;
    $payload[RichiestaIntegrazione::TYPE_DEFAULT] = $idRichiestaIntegrazione;
    $this->payload = $payload;
  }

  /**
   * @return mixed
   */
  public function getRelatedMessages()
  {
    if (isset($this->payload[self::PAYLOAD_MESSAGES])) {
      return $this->payload[self::PAYLOAD_MESSAGES];
    }
    return [];
  }

  /**
   * @param $idRichiestaIntegrazione
   */
  public function setRelatedMessages(array $messages): void
  {
    $payload = $this->payload;
    $payload[self::PAYLOAD_MESSAGES] = $messages;
    $this->payload = $payload;
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

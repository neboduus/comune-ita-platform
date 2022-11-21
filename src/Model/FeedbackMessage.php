<?php


namespace App\Model;


use App\Entity\Pratica;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use OpenApi\Annotations as OA;

class FeedbackMessage implements \JsonSerializable
{
  const STATUS_NAMES = [
    Pratica::STATUS_PRE_SUBMIT => 'Inviata',
    Pratica::STATUS_SUBMITTED => 'Acquisita',
    Pratica::STATUS_REGISTERED => 'Protocollata',
    Pratica::STATUS_PENDING => 'Presa in carico',
    Pratica::STATUS_COMPLETE => 'Iter completato',
    Pratica::STATUS_CANCELLED => 'Rifiutata',
    Pratica::STATUS_WITHDRAW => 'Ritirata',
    Pratica::STATUS_DRAFT => 'Bozza',
  ];

  /**
   * @var string
   * @OA\Property(description="Feedback message status name", type="string")
   * @Serializer\Type("string")
   * @Serializer\Exclude()
   */
  private $name;

  /**
   * @var string
   * @OA\Property(description="Status name trigger status", type="string")
   * @Serializer\Type("int")
   * @Serializer\Exclude()
   */
  private $trigger;

  /**
   * @var string
   * @OA\Property(description="Feedback message", type="string")
   * @Serializer\Type("string")
   * @Groups({"read", "write"})
   */
  private $message;

  /**
   * @var string
   * @OA\Property(description="Feedback subject", type="string")
   * @Serializer\Type("string")
   * @Groups({"read", "write"})
   */
  private $subject;

  /**
   * @var boolean
   * @OA\Property(description="Is feedback message active?", type="boolean")
   * @Serializer\Type("bool")
   * @Groups({"read", "write"})
   */
  private $isActive;

  /**
   * @return string
   */
  public function getName(): ?string
  {
    return $this->name;
  }

  /**
   * @param string|null $name
   */
  public function setName(?string $name)
  {
    $this->name = $name;
  }

  /**
   * @return string
   */
  public function getTrigger(): ?string
  {
    return $this->trigger;
  }

  /**
   * @param string|null $trigger
   */
  public function setTrigger(?string $trigger)
  {
    $this->trigger = $trigger;
  }

  /**
   * @return string
   */
  public function getMessage(): string
  {
    return $this->message;
  }

  /**
   * @param mixed $message
   */
  public function setMessage($message)
  {
    $this->message = $message;
  }

  /**
   * @return string
   */
  public function getSubject(): string
  {
    return $this->subject;
  }

  /**
   * @param mixed $subject
   */
  public function setSubject($subject)
  {
    $this->subject = $subject;
  }

  /**
   * @return bool
   */
  public function isActive(): ?bool
  {
    return $this->isActive;
  }

  /**
   * @param bool $isActive
   */
  public function setIsActive(?bool $isActive)
  {
    $this->isActive = $isActive;
  }

  public function jsonSerialize(): array
  {
    return [
      'name' => $this->getName(),
      'trigger' => $this->getTrigger(),
      'subject' => $this->getSubject(),
      'message' => $this->getMessage(),
      'is_active' => $this->isActive()
    ];
  }

}

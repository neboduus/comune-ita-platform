<?php


namespace AppBundle\Model;


class FeedbackMessage implements \JsonSerializable
{

  /**
   * @var string
   */
  private $name;

  /**
   * @var string
   */
  private $trigger;

  /**
   * @var
   */
  private $message;

  /**
   * @var
   */
  private $subject;

  /**
   * @var boolean
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
   * @param string $name
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
   * @param string $trigger
   */
  public function setTrigger(?string $trigger)
  {
    $this->trigger = $trigger;
  }

  /**
   * @return mixed
   */
  public function getMessage()
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
   * @return mixed
   */
  public function getSubject()
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
  public function isActive(): bool
  {
    return $this->isActive;
  }

  /**
   * @param bool $isActive
   */
  public function setIsActive(bool $isActive)
  {
    $this->isActive = $isActive;
  }

  public function jsonSerialize()
  {
    return get_object_vars($this);
  }

}

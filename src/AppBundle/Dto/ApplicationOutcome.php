<?php

namespace AppBundle\Dto;

use Ramsey\Uuid\Uuid;

class ApplicationOutcome
{
  /**
   * @var string Uuid
   */
  private $applicationId;

  /**
   * @var int
   */
  private $outcome;

  /**
   * @var string
   */
  private $message;

  /**
   * @var string
   */
  private $attachments;

  /**
   * @return string
   */
  public function getApplicationId()
  {
    return $this->applicationId;
  }

  /**
   * @param Uuid $applicationId
   * @return ApplicationOutcome
   */
  public function setApplicationId($applicationId): ApplicationOutcome
  {
    $this->applicationId = $applicationId;

    return $this;
  }

  /**
   * @return int
   */
  public function getOutcome()
  {
    return (int)$this->outcome;
  }

  /**
   * @param int $outcome
   * @return ApplicationOutcome
   */
  public function setOutcome(int $outcome): ApplicationOutcome
  {
    $this->outcome = $outcome;

    return $this;
  }

  /**
   * @return string
   */
  public function getMessage()
  {
    return (string)$this->message;
  }

  /**
   * @param string $message
   * @return ApplicationOutcome
   */
  public function setMessage(?string $message): ApplicationOutcome
  {
    $this->message = $message;

    return $this;
  }

  /**
   * @return array
   */
  public function getAttachments()
  {
    return json_decode($this->attachments, true);
  }

  /**
   * @param array $attachments
   * @return ApplicationOutcome
   */
  public function setAttachments($attachments): ApplicationOutcome
  {
    $this->attachments = $attachments;

    return $this;
  }


}

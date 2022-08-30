<?php

namespace App\Dto;

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
   * @var float
   */
  private $paymentAmount;

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

  /**
   * @return float
   */
  public function getPaymentAmount(): ?float
  {
    return $this->paymentAmount;
  }

  /**
   * @param float $paymentAmount
   */
  public function setPaymentAmount(?float $paymentAmount): void
  {
    $this->paymentAmount = $paymentAmount;
  }

}

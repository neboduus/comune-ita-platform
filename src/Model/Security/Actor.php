<?php

namespace App\Model\Security;

class Actor
{

  private ?string $userId = null;

  private ?string $sessionId = null;

  /**
   * @return string
   */
  public function getUserId(): ?string
  {
    return $this->userId;
  }

  /**
   * @param string|null $userId
   */
  public function setUserId(?string $userId): void
  {
    $this->userId = $userId;
  }

  /**
   * @return string
   */
  public function getSessionId(): ?string
  {
    return $this->sessionId;
  }

  /**
   * @param string|null $sessionId
   */
  public function setSessionId(?string $sessionId): void
  {
    $this->sessionId = $sessionId;
  }



}

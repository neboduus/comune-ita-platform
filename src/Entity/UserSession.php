<?php

namespace App\Entity;

use App\Dto\UserAuthenticationData;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class UserSession
 * @ORM\Entity
 * @ORM\Table(name="sessioni_utente")
 * @package App\Entity
 */
class UserSession
{
  use TimestampableEntity;

  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   */
  private $id;

  /**
   * @ORM\Column(type="guid")
   */
  private $userId;

  /**
   * @var array
   *
   * @ORM\Column(type="json")
   */
  private $sessionData;

  /**
   * @ORM\Column(type="json", nullable=true)
   */
  private $authenticationData;

  /**
   * @var string
   *
   * @ORM\Column(type="string")
   */
  private $ip;

  /**
   * @var string
   *
   * @ORM\Column(type="text")
   */
  private $environment;

  /**
   * @var boolean
   *
   * @ORM\Column(type="boolean")
   */
  private $suspiciousActivity;

  public function __construct()
  {
    if (!$this->id) {
      $this->id = Uuid::uuid4();
    }
  }

  /**
   * @return UuidInterface
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * @param UuidInterface $id
   */
  public function setId(UuidInterface $id)
  {
    $this->id = $id;
  }

  /**
   * @return mixed
   */
  public function getUserId()
  {
    return $this->userId;
  }

  /**
   * @param mixed $userId
   */
  public function setUserId($userId)
  {
    $this->userId = $userId;
  }

  /**
   * @return array
   */
  public function getSessionData()
  {
    return $this->sessionData;
  }

  /**
   * @param array $sessionData
   */
  public function setSessionData(array $sessionData)
  {
    $this->sessionData = $sessionData;
  }

  /**
   * @return UserAuthenticationData
   */
  public function getAuthenticationData()
  {
    $data = $this->authenticationData;
    if (is_string($data)) {
      $data = (array)json_decode($data, true);
    }

    return UserAuthenticationData::fromArray((array)$data);
  }

  /**
   * @param UserAuthenticationData $authenticationData
   */
  public function setAuthenticationData(UserAuthenticationData $authenticationData)
  {
    $this->authenticationData = $authenticationData;
  }

  /**
   * @return string
   */
  public function getClientIp()
  {
    return $this->ip;
  }

  /**
   * @param string $ip
   */
  public function setClientIp(string $ip)
  {
    $this->ip = $ip;
  }

  /**
   * @return string
   */
  public function getEnvironment()
  {
    return $this->environment;
  }

  /**
   * @param string $environment
   */
  public function setEnvironment(string $environment)
  {
    $this->environment = $environment;
  }

  /**
   * @return bool
   */
  public function isSuspiciousActivity()
  {
    return $this->suspiciousActivity;
  }

  /**
   * @param bool $suspiciousActivity
   */
  public function setSuspiciousActivity(bool $suspiciousActivity)
  {
    $this->suspiciousActivity = $suspiciousActivity;
  }

}

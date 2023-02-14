<?php

namespace App\Model\Security;


use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractSecurityLog implements SecurityLogInterface
{

  private string $source = SecurityLogInterface::SOURCE_WEB;

  protected string $action = 'default';

  protected ?array $origin = null;

  private ?Actor $actor;

  private string $shortDescription;

  private ?array $meta = null;

  /**
   * @Serializer\Exclude()
   */
  protected ?UserInterface $user;

  /**
   * @Serializer\Exclude()
   */
  protected $subject;

  /**
   * @param UserInterface|null $user
   * @param null $subject
   */
  public function __construct(?UserInterface $user = null, $subject = null)
  {
    $this->user = $user;
    $this->subject = $subject;

    $this->actor = new Actor();
    if ($user instanceof UserInterface) {
      $this->actor->setUserId($user->getId());
    }

  }

  /**
   * @return string
   */
  public function getSource(): string
  {
    return $this->source;
  }

  /**
   * @param string $source
   */
  public function setSource(string $source): void
  {
    $this->source = $source;
  }

  /**
   * @return string
   */
  public function getAction(): string
  {
    return $this->action;
  }

  /**
   * @return array|null
   */
  public function getOrigin(): ?array
  {
    return $this->origin;
  }

  /**
   * @param array|null $origin
   */
  public function setOrigin(?array $origin): void
  {
    $this->origin = $origin;
  }

  /**
   * @return Actor
   */
  public function getActor(): ?Actor
  {
    return $this->actor;
  }

  /**
   * @param Actor|null $actor
   */
  public function setActor(?Actor $actor): void
  {
    $this->actor = $actor;
  }

  /**
   * @return string
   */
  public function getShortDescription(): string
  {
    return $this->shortDescription;
  }

  /**
   * @param string $shortDescription
   */
  public function setShortDescription(string $shortDescription): void
  {
    $this->shortDescription = $shortDescription;
  }

  /**
   * @return array
   */
  public function getMeta(): ?array
  {
    return $this->meta;
  }

  /**
   * @param array|null $meta
   */
  public function setMeta(?array $meta): void
  {
    $this->meta = $meta;
  }

  /**
   * @return UserInterface|null
   */
  public function getUser(): ?UserInterface
  {
    return $this->user;
  }

  /**
   * @param UserInterface|null $user
   */
  public function setUser(?UserInterface $user): void
  {
    $this->user = $user;
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
  public function setSubject($subject): void
  {
    $this->subject = $subject;
  }

  public function addOriginToDescription(string $description)
  {
    $placeholder = [];
    if (!empty($this->origin)) {
      if (isset($this->origin['ip']) && !empty($this->origin['ip'])) {
        $placeholder['%ip%'] = $this->origin['ip'];
      }

      if (isset($this->origin['city']) && !empty($this->origin['city'])) {
        $placeholder['%city%'] = $this->origin['city'];
      }

      if (isset($this->origin['country']) && !empty($this->origin['country'])) {
        $placeholder['%country%'] = $this->origin['city'];
      }
    }

    return strtr($description, $placeholder);
  }
}

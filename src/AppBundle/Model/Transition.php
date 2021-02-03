<?php


namespace AppBundle\Model;


use AppBundle\Entity\StatusChange;
use AppBundle\Entity\User;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

class Transition
{
  /**
   * @Assert\NotBlank(message="This field is mandatory: Status code")
   * @Assert\NotNull(message="This field is mandatory: Status code")
   * @Serializer\Type("int")
   * @SWG\Property(description="Status code", type="integer")
   * @Groups({"read"})
   */
  private $statusCode;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Status name")
   * @Groups({"read"})
   */
  private $statusName;

  /**
   * @var User
   * @Serializer\Exclude()
   */
  private $user;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Status Message")
   * @Groups({"read", "write"})
   */
  private $message;

  /**
   * @var User
   * @Serializer\Type("string")
   * @SWG\Property(description="Status Message Id")
   * @Groups({"read"})
   */
  private $messageId;

  /**
   * @Serializer\Type("DateTime")
   * @SWG\Property(description="Transition date time", type="string")
   * @Groups({"read"})
   */
  private $date;

  /**
   * Transition constructor.
   */
  public function __construct()
  {
    $this->date = new \DateTime();
  }


  /**
   * @return mixed
   */
  public function getStatusCode()
  {
    return $this->statusCode;
  }

  /**
   * @param mixed $statusCode
   */
  public function setStatusCode($statusCode): void
  {
    $this->statusCode = $statusCode;
  }

  /**
   * @return mixed
   */
  public function getStatusName()
  {
    return $this->statusName;
  }

  /**
   * @param mixed $statusName
   */
  public function setStatusName($statusName): void
  {
    $this->statusName = $statusName;
  }

  /**
   * @return mixed
   */
  public function getUser()
  {
    return $this->user;
  }

  /**
   * @param mixed $user
   */
  public function setUser($user): void
  {
    $this->user = $user;
  }

  /**
   * @return mixed
   */
  public function getMessageId()
  {
    return $this->messageId;
  }

  /**
   * @param mixed $messageId
   */
  public function setMessageId($messageId): void
  {
    $this->messageId = $messageId;
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
  public function setMessage($message): void
  {
    $this->message = $message;
  }

  /**
   * @return mixed
   */
  public function getDate()
  {
    return $this->date;
  }

  /**
   * @param mixed $date
   */
  public function setDate($date): void
  {
    $this->date = $date;
  }

  public function toStatusChange()
  {
    $data = [
      'message' => $this->message,
      'time' => $this->date->getTimestamp()
    ];
    if ($this->user instanceof User) {
      $data['operatore'] = $this->user->getFullName();
    }
    return new StatusChange($data);
  }

}

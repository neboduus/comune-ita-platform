<?php


namespace AppBundle\Dto;


use AppBundle\Entity\Allegato;
use AppBundle\Entity\AllegatoMessaggio;
use AppBundle\Entity\Pratica;
use AppBundle\Model\File;
use AppBundle\Utils\StringUtils;
use DateTime;
use Exception;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;
use Nelmio\ApiDocBundle\Annotation\Model;
use \AppBundle\Entity\Message as MessageEntity;

class Message
{

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Message's uuid", type="string")
   * @Groups({"read"})
   */
  private $id;

  /**
   * @Assert\NotBlank(message="Message is mandatory")
   * @Assert\NotNull(message="Message is mandatory")
   * @Serializer\Type("string")
   * @SWG\Property(description="Message, accepts html tags")
   * @Groups({"read", "write"})
   */
  private $message;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Message subject")
   * @Groups({"read", "write"} )
   */
  private $subject;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Author of the message (uuid)")
   * @Groups({"read"})
   */
  private $author;

  /**
   * @Assert\NotBlank(message="Application is mandatory")
   * @Assert\NotNull(message="Application is mandatory")
   * @Serializer\Type("string")
   * @SWG\Property(description="Application of the message (uuid)")
   * @Groups({"read"})
   */
  private $application;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Visibility ")
   * @Groups({"read", "write"})
   */
  private $visibility;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Email the message was sent to")
   * @Groups({"read", "write"})
   */
  private $email;

  /**
   * @var DateTime
   * @Serializer\Type("DateTime")
   * @SWG\Property(description="Created at date time")
   * @Groups({"read"})
   */
  private $createdAt;

  /**
   * @var DateTime|null
   * @Serializer\Type("DateTime")
   * @SWG\Property(description="Sent at date time")
   * @Groups({"read", "write"})
   */
  private $sentAt;

  /**
   * @var DateTime|null
   * @Serializer\Type("DateTime")
   * @SWG\Property(description="Read date time")
   * @Groups({"read", "write"})
   */
  private $readAt;

  /**
   * @var DateTime|null
   * @Serializer\Type("DateTime")
   * @SWG\Property(description="Clicked at date time")
   * @Groups({"read", "write"})
   */
  private $clickedAt;

  /**
   * @var File[]
   * @SWG\Property(property="attachments", type="array", @SWG\Items(ref=@Model(type=File::class, groups={"read", "write"})))
   * @Serializer\Type("array")
   * @Groups({"read", "write"})
   */
  private $attachments;

  /**
   * @var bool
   * @Serializer\Type("bool")
   * @SWG\Property(description="Is protocol required?")
   * @Groups({"read", "write"})
   */
  private $protocolRequired;

  /**
   * @var DateTime|null
   * @Serializer\Type("DateTime")
   * @SWG\Property(description="Protocolled at date time")
   * @Groups({"read", "write"})
   */
  private $protocolledAt;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Protocol number")
   * @Groups({"read", "write"})
   */
  private $protocolNumber;

  /**
   * @return mixed
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * @param mixed $id
   */
  public function setId($id): void
  {
    $this->id = $id;
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

  /**
   * @return mixed
   */
  public function getAuthor()
  {
    return $this->author;
  }

  /**
   * @param mixed $author
   */
  public function setAuthor($author): void
  {
    $this->author = $author;
  }

  /**
   * @return mixed
   */
  public function getApplication()
  {
    return $this->application;
  }

  /**
   * @param mixed $application
   */
  public function setApplication($application): void
  {
    $this->application = $application;
  }

  /**
   * @return mixed
   */
  public function getVisibility()
  {
    return $this->visibility;
  }

  /**
   * @param mixed $visibility
   */
  public function setVisibility($visibility): void
  {
    $this->visibility = $visibility;
  }

  public function getEmail(): ?string
  {
    return $this->email;
  }

  /**
   * @param string $email
   */
  public function setEmail(string $email)
  {
    $this->email = $email;
  }

  /**
   * @return mixed
   */
  public function getCreatedAt()
  {
    return $this->createdAt;
  }

  /**
   * @param mixed $createdAt
   */
  public function setCreatedAt($createdAt): void
  {
    $this->createdAt = $createdAt;
  }

  /**
   * @return mixed
   */
  public function getSentAt()
  {
    return $this->sentAt;
  }

  /**
   * @param mixed $sentAt
   */
  public function setSentAt(?\DateTime $sentAt): void
  {
    $this->sentAt = $sentAt;
  }

  /**
   * @return mixed
   */
  public function getReadAt()
  {
    return $this->readAt;
  }

  /**
   * @param mixed $readAt
   */
  public function setReadAt(?DateTime $readAt): void
  {
    $this->readAt = $readAt;
  }

  /**
   * @return mixed
   */
  public function getClickedAt()
  {
    return $this->clickedAt;
  }

  /**
   * @param mixed $clickedAt
   */
  public function setClickedAt(?DateTime $clickedAt): void
  {
    $this->clickedAt = $clickedAt;
  }

  /**
   * @return File[]
   */
  public function getAttachments(): ?array
  {
    return $this->attachments;
  }

  /**
   * @param File[] $attachments
   */
  public function setAttachments(array $attachments): void
  {
    $this->attachments = $attachments;
  }

  /**
   * @return bool
   */
  public function isProtocolRequired(): ?bool
  {
    return $this->protocolRequired;
  }

  /**
   * @param bool $protocolRequired
   */
  public function setProtocolRequired(bool $protocolRequired): void
  {
    $this->protocolRequired = $protocolRequired;
  }

  /**
   * @return mixed
   */
  public function getProtocolledAt()
  {
    return $this->protocolledAt;
  }

  /**
   * @param mixed $protocolledAt
   */
  public function setProtocolledAt(?DateTime $protocolledAt): void
  {
    $this->protocolledAt = $protocolledAt;
  }

  /**
   * @return mixed
   */
  public function getProtocolNumber()
  {
    return $this->protocolNumber;
  }

  /**
   * @param mixed $protocolNumber
   */
  public function setProtocolNumber($protocolNumber): void
  {
    $this->protocolNumber = $protocolNumber;
  }

  /**
   * @param MessageEntity $message
   * @return Message
   */
  public static function fromEntity(MessageEntity $message, $applicationBaseUrl)
  {
    $dto = new self();
    $dto->id = $message->getId();
    $dto->message = $message->getMessage();
    $dto->subject = $message->getSubject();
    $dto->author = $message->getAuthor() ? $message->getAuthor()->getId() : null;
    $dto->application = $message->getApplication()->getId();
    $dto->visibility = $message->getVisibility();
    $dto->email = $message->getEmail();

    $dto->createdAt = self::dateTimeFromTimestamp($message->getCreatedAt());
    $dto->sentAt = self::dateTimeFromTimestamp($message->getSentAt());
    $dto->readAt = self::dateTimeFromTimestamp($message->getReadAt());
    $dto->clickedAt = self::dateTimeFromTimestamp($message->getClickedAt());
    $dto->protocolledAt = self::dateTimeFromTimestamp($message->getProtocolledAt());

    $dto->protocolRequired = $message->isProtocolRequired();
    $dto->protocolNumber = $message->getProtocolNumber();

    $dto->attachments = self::prepareFileCollection(
      $message->getAttachments(),
      $applicationBaseUrl . '/messages/' . $message->getId()
    );

    return $dto;
  }

  /**
   * @param MessageEntity|null $entity
   * @return MessageEntity
   */
  public function toEntity(MessageEntity $entity = null)
  {
    if (!$entity) {
      $entity = new MessageEntity();
    }

    $entity->setMessage($this->getMessage());
    $entity->setSubject($this->getSubject());
    if ($this->getAuthor() instanceof \AppBundle\Entity\User) {
      $entity->setAuthor($this->getAuthor());
    }
    if ($this->getApplication() instanceof Pratica) {
      $entity->setApplication($this->getApplication());
    }
    $entity->setVisibility($this->getVisibility());
    $entity->setEmail($this->getEmail());
    if ($this->getCreatedAt() instanceof DateTime) {
      $entity->setCreatedAt($this->getCreatedAt()->getTimestamp());
    }
    if ($this->getSentAt() instanceof DateTime) {
      $entity->setSentAt($this->getSentAt()->getTimestamp());
    }
    if ($this->getReadAt() instanceof DateTime) {
      $entity->setReadAt($this->getReadAt()->getTimestamp());
    }
    if ($this->getClickedAt() instanceof DateTime) {
      $entity->setClickedAt($this->getClickedAt()->getTimestamp());
    }
    if ($this->getProtocolledAt() instanceof DateTime) {
      $entity->setProtocolledAt($this->getProtocolledAt()->getTimestamp());
    }
    $entity->setProtocolRequired($this->isProtocolRequired());
    $entity->setProtocolNumber($this->getProtocolNumber());

    // Update attachment protocol data
    if ($entity->getProtocolNumber()) {
      foreach ($entity->getAttachments() as $attachment) {
        /** @var AllegatoMessaggio $attachment */
        if (!$attachment->getNumeroProtocollo()) {
          $attachment->setNumeroProtocollo($entity->getProtocolNumber());
          $attachment->addNumeroDiProtocollo([
            "id" => null,
            "protocollo" => $entity->getProtocolNumber()
          ]);
        }
      }
    }


    return $entity;
  }

  /**
   * @param $collection
   * @param string $attachmentEndpointUrl
   * @return array
   */
  public static function prepareFileCollection( $collection, $attachmentEndpointUrl = '')
  {
    $files = [];
    if ( $collection == null) {
      return $files;
    }
    /** @var Allegato $c */
    foreach ($collection as $c) {
      $files[]= self::prepareFile($c, $attachmentEndpointUrl);
    }
    return $files;
  }

  /**
   * @param Allegato $file
   * @param string $attachmentEndpointUrl
   * @return mixed
   */
  public static function prepareFile(Allegato $file, $attachmentEndpointUrl = '')
  {
    /*

    $filename = $file->getOriginalFilename();
    $filenameParts = explode('.', $filename);
    $systemFilename = $file->getFilename();
    $systemFilenameParts = explode('.', $systemFilename);
    if (end($filenameParts) != end($systemFilenameParts)) {
      $filename .=  '.' . end($systemFilenameParts);
    }

    $description = $file->getDescription();
    if (empty($description) || $description === $filename) {
      $description =  Allegato::DEFAULT_DESCRIPTION . ' - ' . $filename;
    } else {
      $description .= ' - ' . $filename;
    }

    $temp['id'] = $file->getId();
    $temp['name'] = $systemFilename;
    $temp['url'] = $baseUrl . '/attachments/' . $file->getId() . '?version=' . $version;
    $temp['originalName'] = StringUtils::sanitizeFileName($filename);
    $temp['description'] = $description;
    $temp['created_at'] = $file->getCreatedAt();
    $temp['protocol_required'] = $file->isProtocolRequired();
    $temp['protocol_number'] = $file->getNumeroProtocollo() ?: ($file->getIdDocumentoProtocollo() ?: null);

    */

    $filename = $file->getOriginalFilename();
    $filenameParts = explode('.', $filename);
    $systemFilename = $file->getFilename();
    $systemFilenameParts = explode('.', $systemFilename);
    if (end($filenameParts) != end($systemFilenameParts)) {
      $filename .=  '.' . end($systemFilenameParts);
    }

    $description = $file->getDescription();
    if (empty($description) || $description === $filename) {
      $description =  Allegato::DEFAULT_DESCRIPTION . ' - ' . $filename;
    } else {
      $description .= ' - ' . $filename;
    }

    $temp = new File();
    $temp->setId($file->getId());
    $temp->setName($systemFilename);
    $temp->setUrl($attachmentEndpointUrl . '/attachments/' .  $file->getId());
    $temp->setOriginalName(StringUtils::sanitizeFileName($filename));
    $temp->setDescription($description);
    $temp->setCreatedAt($file->getCreatedAt());
    $temp->setProtocolRequired($file->isProtocolRequired());
    $temp->setProtocolNumber($file->getNumeroProtocollo() ?: ($file->getIdDocumentoProtocollo() ?: null));
    //$temp->getMimeType($file->getFile()->getMimeType());
    return $temp;
  }
  /**
   * @param $value
   * @return DateTime|null
   */
  public static function dateTimeFromTimestamp($value)
  {
    try {
      if ($value > 0) {
        $date = new DateTime();
        return $date->setTimestamp($value);
      }
    } catch (Exception $e) {
      return null;
    }
    return null;
  }

}

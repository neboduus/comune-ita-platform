<?php


namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Entity(repositoryClass="App\Entity\PraticaRepository")
 * @ORM\Table(name="message")
 **/
class Message
{
  const VISIBILITY_INTERNAL = 'internal';
  const VISIBILITY_APPLICANT = 'applicant';

  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   */
  private $id;

  /**
   * @var string
   * @ORM\Column(type="text", nullable=false)
   */
  private $message;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\User")
   * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
   */
  private $author;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\Pratica", inversedBy="messages")
   * @ORM\JoinColumn(name="pratica_id", referencedColumnName="id", nullable=false)
   */
  private $application;

  /**
   * @ORM\Column(type="string", nullable=false)
   */
  private $visibility;

  /**
   * @ORM\Column(type="integer", name="created_at", nullable=false)
   */
  private $createdAt;

  /**
   * @ORM\Column(type="integer", name="sent_at", nullable=true)
   */
  private $sentAt;

  /**
   * @ORM\Column(type="integer", name="read_at", nullable=true)
   */
  private $readAt;

  /**
   * @ORM\Column(type="integer", name="clicked_at", nullable=true)
   */
  private $clickedAt;

  /**
   * @ORM\ManyToMany(targetEntity="App\Entity\AllegatoMessaggio", inversedBy="messages", orphanRemoval=false)
   * @var ArrayCollection
   * @Assert\Valid(traverse=true)
   */
  private $attachments;

  /**
   * @ORM\OneToOne(targetEntity="App\Entity\AllegatoMessaggio", orphanRemoval=false)
   * @ORM\JoinColumn(nullable=true)
   * @var AllegatoMessaggio
   */
  private $generatedDocument;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=true, options={"default":"1"})
   */
  private $protocolRequired;

  /**
   * @ORM\Column(type="integer", name="protocolled_at", nullable=true)
   */
  private $protocolledAt;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @var string
   */
  private $protocolNumber;

  /**
   * @ORM\Column(type="json", nullable=true)
   */
  private $callToActions;

  /**
   * Pratica constructor.
   */
  public function __construct()
  {
    if (!$this->id) {
      $this->id = Uuid::uuid4();
    }
    $this->createdAt = time();
    $this->setProtocolRequired(true);
    $this->attachments = new ArrayCollection();
  }

  /**
   * @return string
   */
  public function __toString()
  {
    return (string)$this->getId();
  }

  /**
   * @return mixed
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * @param Uuid $id
   * @return $this
   */
  public function setId(Uuid $id)
  {
    $this->id = $id;
    return $this;
  }

  /**
   * @return string
   */
  public function getMessage()
  {
    return $this->message;
  }

  /**
   * @param string $message
   *
   * @return $this
   */
  public function setMessage($message)
  {
    $this->message = $message;

    return $this;
  }

  /**
   * @return User|CPSUser
   */
  public function getAuthor()
  {
    return $this->author;
  }

  /**
   * @param User $author
   *
   * @return $this
   */
  public function setAuthor(User $author)
  {
    $this->author = $author;

    return $this;
  }

  /**
   * @return Pratica
   */
  public function getApplication()
  {
    return $this->application;
  }

  /**
   * @param Pratica $application
   *
   * @return $this
   */
  public function setApplication(Pratica $application)
  {
    $this->application = $application;

    return $this;
  }

  /**
   * @return string
   */
  public function getVisibility()
  {
    return $this->visibility;
  }

  /**
   * @param string $visibility
   *
   * @return $this
   */
  public function setVisibility($visibility)
  {
    $this->visibility = $visibility;

    return $this;
  }

  /**
   * @return mixed
   */
  public function getSentAt()
  {
    return $this->sentAt;
  }

  /**
   * @param $sentAt
   *
   * @return $this
   */
  public function setSentAt($sentAt)
  {
    $this->sentAt = $sentAt;

    return $this;
  }

  /**
   * @return mixed
   */
  public function getReadAt()
  {
    return $this->readAt;
  }

  /**
   * @param $readAt
   *
   * @return $this
   */
  public function setReadAt($readAt)
  {
    $this->readAt = $readAt;

    return $this;
  }

  /**
   * @return mixed
   */
  public function getClickedAt()
  {
    return $this->clickedAt;
  }

  /**
   * @param $clickedAt
   *
   * @return $this
   */
  public function setClickedAt($clickedAt)
  {
    $this->clickedAt = $clickedAt;

    return $this;
  }

  /**
   * @return Collection
   */
  public function getAttachments()
  {
    return $this->attachments;
  }

  /**
   * @param AllegatoMessaggio $attachment
   * @return $this
   */
  public function addAttachment(AllegatoMessaggio $attachment)
  {
    if (!$this->attachments->contains($attachment)) {
      $this->attachments->add($attachment);
    }

    return $this;
  }

  /**
   * @param AllegatoMessaggio $attachment
   *
   * @return $this
   */
  public function removeAttachment(AllegatoMessaggio $attachment)
  {
    if ($this->attachments->contains($attachment)) {
      $this->attachments->removeElement($attachment);
    }
    return $this;
  }

  /**
   * @return AllegatoMessaggio
   */
  public function getGeneratedDocument()
  {
    return $this->generatedDocument;
  }

  /**
   * @param AllegatoMessaggio $document
   * @return $this
   */
  public function addGeneratedDocument($document)
  {
    $this->generatedDocument = $document;

    return $this;
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
  public function setProtocolRequired(?bool $protocolRequired)
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
   * @param $protocolledAt
   *
   * @return $this
   */
  public function setProtocolledAt($protocolledAt)
  {
    $this->protocolledAt = $protocolledAt;

    return $this;
  }

  /**
   * @return array
   */
  public function getCallToAction(): ?array
  {
    return $this->callToActions;
  }

  /**
   * @param array $callToActions
   */
  public function setCallToAction(array $callToActions)
  {
    $this->callToActions = $callToActions;
  }

  /**
   * @return mixed
   */
  public function getCreatedAt()
  {
    return $this->createdAt;
  }

  /**
   * @param integer $time
   *
   * @return $this
   */
  public function setCreatedAt($time)
  {
    $this->createdAt = $time;

    return $this;
  }

}

<?php

namespace AppBundle\Entity;

use AppBundle\Model\SubscriptionPayment;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * SubscriptionService
 * @ORM\Entity
 * @ORM\Table(name="subscription_service")
 */
class SubscriptionService
{
  const STATUS_WAITING = 0;
  const STATUS_ACTIVE = 1;
  const STATUS_UNACTIVE = 2;

  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   */
  private $id;

  /**
   * @var string
   * @Assert\NotBlank (message="Il campo nome è un parametro obbligatorio")
   * @ORM\Column(name="code", type="string", length=255, unique=true)
   */
  private $code;

  /**
   * @var string
   * @Assert\NotBlank (message="Il campo codice è un parametro obbligatorio")
   * @ORM\Column(name="name", type="string", length=255)
   */
  private $name;

  /**
   * @var string
   * @Assert\NotBlank (message="Il campo descrizione è un parametro obbligatorio")
   * @ORM\Column(name="description", type="text", nullable=true)
   */
  private $description;

  /**
   * @var \DateTime
   *
   * @Assert\NotBlank (message="Il campo data di inizio iscrizioni è un parametro obbligatorio")
   * @ORM\Column(name="subscription_begin", type="datetime")
   * @Assert\LessThanOrEqual(propertyPath="subscriptionEnd",  message="La data di inizio iscrizione deve essere minore o oguale alla data di fine iscrizione")
   * @Assert\LessThanOrEqual(propertyPath="beginDate", message="La data di inizio iscrizione deve essere minore o oguale alla data di inizio corso")
   * @Assert\LessThanOrEqual(propertyPath="endDate",  message="La data di inizio iscrizione deve essere minore o oguale alla data di fine corso")
   */
  private $subscriptionBegin;

  /**
   * @var \DateTime
   *
   * @Assert\NotBlank (message="Il campo data di fine iscrizioni è un parametro obbligatorio")
   * @ORM\Column(name="subscription_end", type="datetime")
   */
  private $subscriptionEnd;

  /**
   * @var int
   *
   * @ORM\Column(name="subscription_amount", type="decimal", options={"default": 0})
   */
  private $subscriptionAmount = 0;

  /**
   * @var \DateTime
   *
   * @Assert\NotBlank (message="Il campo data di inizio è un parametro obbligatorio")
   * @Assert\LessThanOrEqual(propertyPath="endDate",  message="La data di inizio corso deve essere minore o oguale alla data di fine corso")
   * @ORM\Column(name="begin_date", type="datetime")
   */
  private $beginDate;

  /**
   * @var \DateTime
   *
   * @Assert\NotBlank (message="Il campo data di inizio è un parametro obbligatorio")
   * @ORM\Column(name="end_date", type="datetime")
   */
  private $endDate;

  /**
   * @var int
   *
   * @ORM\Column(name="subscribers_limit", type="integer", nullable=true)
   */
  private $subscribersLimit;

  /**
   * @var string
   *
   * @ORM\Column(name="subscription_message", type="text", length=255, nullable=true)
   */
  private $subscriptionMessage;

  /**
   * @var string
   *
   * @ORM\Column(name="begin_message", type="text", length=255, nullable=true)
   */
  private $beginMessage;

  /**
   * @var string
   *
   * @ORM\Column(name="end_message", type="text", nullable=true)
   */
  private $endMessage;

  /**
   * @ORM\Column(type="integer")
   * @Assert\NotBlank(message="Seleziona un'opzione. Lo stato è un parametro obbligatorio")
   * @Assert\NotNull()
   */
  private $status;

  /**
   * @var array
   * @ORM\Column(name="payments", type="json_array", nullable=true)
   */
  protected $subscriptionPayments;

  /**
   * @var string[]
   * @ORM\Column(name="tags", type="array", nullable=true)
   */
  protected $tags;


  /**
   * @ORM\OneToMany(targetEntity="AppBundle\Entity\Subscription", mappedBy="subscription_service")
   */
  private $subscriptions;

  /**
   * @ORM\Column(type="datetime", options={"default"="CURRENT_TIMESTAMP"})
   */
  private $created_at;

  /**
   * Servizio constructor.
   */
  public function __construct()
  {
    if (!$this->id) {
      $this->id = Uuid::uuid4();
    }
    $this->setCreatedAt(new \DateTime('now'));
    $this->subscriptions = new ArrayCollection();
    $this->subscriptionPayments = [];
    $this->tags = [];
    $this->status = self::STATUS_UNACTIVE;
  }

  /**
   * get id
   *
   * @return UuidInterface
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * @return Collection|Subscription[]
   */
  public function getSubscriptions(): Collection
  {
    return $this->subscriptions;
  }

  public function addSubscription(Subscription $subscription): self
  {
    if (!$this->subscriptions->contains($subscription)) {
      $this->subscriptions[] = $subscription;
      $subscription->setSubscriptionService($this);
    }

    return $this;
  }

  public function removeSubscription(Subscription $subscription): self
  {
    if ($this->subscriptions->contains($subscription)) {
      $this->subscriptions->removeElement($subscription);
      // set the owning side to null (unless already changed)
      if ($subscription->getSubscriptionService() === $this) {
        $subscription->setSubscriptionService(null);
      }
    }

    return $this;
  }

  /**
   * Set code.
   *
   * @param string $code
   *
   * @return SubscriptionService
   */
  public function setCode($code)
  {
    $this->code = $code;

    return $this;
  }

  /**
   * Get code.
   *
   * @return string
   */
  public function getCode()
  {
    return $this->code;
  }

  /**
   * Set name.
   *
   * @param string $name
   *
   * @return SubscriptionService
   */
  public function setName($name)
  {
    $this->name = $name;

    return $this;
  }

  /**
   * Get name.
   *
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * @return string
   */
  public function getDescription()
  {
    return $this->description;
  }

  /**
   * @param string $description
   *
   * @return $this
   */
  public function setDescription($description)
  {
    $this->description = $description;

    return $this;
  }

  /**
   * Set subscriptionBegin.
   *
   * @param \DateTime $subscriptionBegin
   *
   * @return SubscriptionService
   */
  public function setSubscriptionBegin($subscriptionBegin)
  {
    $this->subscriptionBegin = $subscriptionBegin;

    return $this;
  }

  /**
   * Get subscriptionBegin.
   *
   * @return \DateTime
   */
  public function getSubscriptionBegin()
  {
    return $this->subscriptionBegin;
  }

  /**
   * Set subscriptionEnd.
   *
   * @param \DateTime $subscriptionEnd
   *
   * @return SubscriptionService
   */
  public function setSubscriptionEnd($subscriptionEnd)
  {
    $this->subscriptionEnd = $subscriptionEnd;

    return $this;
  }

  /**
   * Get subscriptionEnd.
   *
   * @return \DateTime
   */
  public function getSubscriptionEnd()
  {
    return $this->subscriptionEnd;
  }

  /**
   * Set subscriptionAmount.
   *
   * @param integer $subscriptionAmount
   *
   * @return SubscriptionService
   */
  public function setSubscriptionAmount($subscriptionAmount)
  {
    if (!$subscriptionAmount) {
      $this->subscriptionAmount = 0;
    } else {
      $this->subscriptionAmount = $subscriptionAmount;
    }

    return $this;
  }

  /**
   * Get subscriptionAmount.
   *
   * @return integer
   */
  public function getSubscriptionAmount()
  {
    return $this->subscriptionAmount;
  }

  /**
   * Set beginDate.
   *
   * @param \DateTime $beginDate
   *
   * @return SubscriptionService
   */
  public function setBeginDate($beginDate)
  {
    $this->beginDate = $beginDate;

    return $this;
  }

  /**
   * Get beginDate.
   *
   * @return \DateTime
   */
  public function getBeginDate()
  {
    return $this->beginDate;
  }

  /**
   * Set endDate.
   *
   * @param \DateTime $endDate
   *
   * @return SubscriptionService
   */
  public function setEndDate($endDate)
  {
    $this->endDate = $endDate;

    return $this;
  }

  /**
   * Get endDate.
   *
   * @return \DateTime
   */
  public function getEndDate()
  {
    return $this->endDate;
  }

  /**
   * Set subscribersLimit.
   *
   * @param integer $subscribersLimit
   *
   * @return SubscriptionService
   */
  public function setSubscribersLimit($subscribersLimit)
  {
    $this->subscribersLimit = $subscribersLimit;

    return $this;
  }

  /**
   * Get subscribersLimit.
   *
   * @return integer
   */
  public function getSubscribersLimit()
  {
    return $this->subscribersLimit;
  }

  /**
   * Set subscriptionMessage.
   *
   * @param string $subscriptionMessage
   *
   * @return SubscriptionService
   */
  public function setSubscriptionMessage($subscriptionMessage)
  {
    $this->subscriptionMessage = $subscriptionMessage;

    return $this;
  }

  /**
   * Get subscriptionMessage.
   *
   * @return string
   */
  public function getSubscriptionMessage()
  {
    return $this->subscriptionMessage;
  }

  /**
   * Set beginMessage.
   *
   * @param string $beginMessage
   *
   * @return SubscriptionService
   */
  public function setBeginMessage($beginMessage)
  {
    $this->beginMessage = $beginMessage;

    return $this;
  }

  /**
   * Get beginMessage.
   *
   * @return string
   */
  public function getBeginMessage()
  {
    return $this->beginMessage;
  }

  /**
   * Set endMessage.
   *
   * @param string $endMessage
   *
   * @return SubscriptionService
   */
  public function setEndMessage($endMessage)
  {
    $this->endMessage = $endMessage;

    return $this;
  }

  /**
   * Get endMessage.
   *
   * @return string
   */
  public function getEndMessage()
  {
    return $this->endMessage;
  }

  /**
   * Get status
   *
   * @return mixed
   */
  public function getStatus()
  {
    return $this->status;
  }

  /**
   * Set status
   *
   * @param $status
   *
   * @return $this
   */
  public function setStatus($status)
  {
    $this->status = $status;

    return $this;
  }

  /**
   * @return SubscriptionPayment[]
   * @throws \Exception
   */
  public function getSubscriptionPayments()
  {
    $subscriptionPayments = [];
    foreach ($this->subscriptionPayments as $subscriptionPayment) {
      $payment = new SubscriptionPayment();
      $payment->setDate(new \DateTime($subscriptionPayment['date']['date']));
      $payment->setAmount($subscriptionPayment['amount']);
      $subscriptionPayments[] = $payment;
    }
    return $subscriptionPayments;

  }

  /**
   * @param Collection $subscriptionPayments
   * @return $this
   */
  public function setSubscriptionPayments($subscriptionPayments)
  {
    $this->subscriptionPayments = $subscriptionPayments;
    return $this;
  }

  /**
   * Set id.
   *
   * @param string $id
   *
   * @return SubscriptionService
   */
  public function setId($id)
  {
    $this->id = $id;

    return $this;
  }

  public function getCreatedAt(): ?\DateTimeInterface
  {
    return $this->created_at;
  }

  public function setCreatedAt(\DateTimeInterface $created_at): self
  {
    $this->created_at = $created_at;

    return $this;
  }

  /**
   *
   * @return array
   */
  public function getTags()
  {
    return $this->tags;
  }

  /**
   * Set status
   **
   * @return $this
   */
  public function setTags($tags)
  {
    $this->tags = $tags;

    return $this;
  }
}

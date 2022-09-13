<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use OpenApi\Annotations as OA;

/**
 * @ORM\Entity
 * @ORM\Table(name="subscription",
 *    uniqueConstraints={
 *        @UniqueConstraint(name="subscription_unique",
 *            columns={"subscriber_id", "subscription_service_id"})
 *    }
 * )
 * @ORM\HasLifecycleCallbacks
 */
class Subscription
{

  const STATUS_ACTIVE = 'active';
  const STATUS_WITHDRAW = 'withdraw';

  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   * @OA\Property(description="Subscription's uuid")
   * @Groups({"read"})
   */
  protected $id;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\Subscriber", inversedBy="subscriptions")
   * @ORM\JoinColumn(nullable=false)
   * @Serializer\Exclude()
   * @OA\Property(description="Subscription's subscriber")
   */
  private $subscriber;

  /**
   * @ORM\OneToMany(targetEntity="App\Entity\SubscriptionPayment", mappedBy="subscription")
   * @Serializer\Exclude()
   * @OA\Property(description="Subscription Payments")
   */
  private $subscriptionPayments;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\SubscriptionService", inversedBy="subscriptions")
   * @Serializer\Exclude()
   * @ORM\JoinColumn(nullable=false)
   * @OA\Property(description="Subscription's Subscription Service")
   */
  private $subscription_service;

  /**
   * @ORM\Column(type="json", options={"jsonb":true}, nullable=true)
   * @OA\Property(description="Subscription's related fiscal codes", type="array", @OA\Items(type="string"))
   * @Groups({"read", "write"})
   * @var $relatedCFs array
   */
  private $relatedCFs;

  /**
   * @ORM\Column(type="string", nullable=false, options={"default":"active"})
   * @Serializer\Type("string")
   * @Groups({"read", "write"})
   * @OA\Property(description="Subscription status: available statuses are 'active' or 'withdraw'")
   */
  private $status = self::STATUS_ACTIVE;

  /**
   * @ORM\Column(type="datetime", options={"default"="CURRENT_TIMESTAMP"})
   * @OA\Property(description="Subscription's creation date")
   * @Groups({"read"})
   */
  private $created_at;

  public function __construct()
  {
    $this->id = Uuid::uuid4();
    $this->subscriptionPayments = new ArrayCollection();
    $this->setCreatedAt(new \DateTime('now'));
  }

  /**
   * @return string
   */
  public function __toString()
  {
    return (string)$this->getSubscriptionService()->getCode();
  }

  /**
   * @return UuidInterface
   */
  public function getId()
  {
    return $this->id;
  }

  public function getSubscriber(): ?Subscriber
  {
    return $this->subscriber;
  }

  public function setSubscriber(?Subscriber $subscriber): self
  {
    $this->subscriber = $subscriber;

    return $this;
  }

  /**
   * @return Collection|Subscription[]
   */
  public function getSubscriptionPayments(): Collection
  {
    return $this->subscriptionPayments;
  }

  public function addSubscriptionPayment(SubscriptionPayment $subscriptionPayment): self
  {
    if (!$this->subscriptionPayments->contains($subscriptionPayment)) {
      $this->subscriptionPayments[] = $subscriptionPayment;
      $subscriptionPayment->setSubscription($this);
    }

    return $this;
  }

  public function removeSubscriptionPayment(SubscriptionPayment $subscriptionPayment): self
  {
    if ($this->subscriptionPayments->contains($subscriptionPayment)) {
      $this->subscriptionPayments->removeElement($subscriptionPayment);
      // set the owning side to null (unless already changed)
      if ($subscriptionPayment->getSubscription() === $this) {
        $subscriptionPayment->setSubscription(null);
      }
    }

    return $this;
  }

  public function getSubscriptionService(): ?SubscriptionService
  {
    return $this->subscription_service;
  }

  public function setSubscriptionService(?SubscriptionService $subscriptionService): self
  {
    $this->subscription_service = $subscriptionService;

    return $this;
  }

  public function getRelatedCFs()
  {
    return $this->relatedCFs;
  }

  public function removeRelatedCf($fiscalCode)
  {
    if (!$this->relatedCFs) {
      $this->relatedCFs = [];
    }
    if (($key = array_search($fiscalCode, $this->getRelatedCFs())) !== false) {
      unset($this->relatedCFs[$key]);
    }
    return $this;
  }

  public function addRelatedCf($fiscalCode)
  {
    if (!$this->relatedCFs) {
      $this->relatedCFs = [];
    }
    if (!in_array($fiscalCode, $this->getRelatedCFs())) {
      $this->relatedCFs[] = $fiscalCode;
    }
    return $this;
  }

  /**
   * @param array $relatedCFs
   * @return $this
   */
  public function setRelatedCFs($relatedCFs)
  {
    $this->relatedCFs = $relatedCFs;

    return $this;
  }

  public function getStatus(): string
  {
    return $this->status;
  }

  public function setStatus($status): self
  {
    $this->status = $status;

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
   * Sybscription's subscriber
   *
   * @Serializer\VirtualProperty()
   * @Serializer\Type("string")
   * @Serializer\SerializedName("subscriber")
   * @OA\Property(description="Subscription's subscriber")
   * @Groups({"read", "write"})
   */
  public function getSubscriberId()
  {
    return $this->subscriber->getId();
  }

  /**
   * Sybscription's subscriber
   *
   * @Serializer\VirtualProperty()
   * @Serializer\Type("string")
   * @Serializer\SerializedName("subscription_service")
   * @OA\Property(description="Subscription's subscription service")
   * @Groups({"read", "write"})
   */
  public function getSubscriptionServiceId()
  {
    return $this->subscription_service->getId();
  }

  /**
   * @return bool
   */
  public function isActive(): bool
  {
    return $this->status === self::STATUS_ACTIVE;
  }
}

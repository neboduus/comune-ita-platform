<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use JMS\Serializer\Annotation as Serializer;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Swagger\Annotations as SWG;

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
  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   * @SWG\Property(description="Subscription's uuid")
   */
  protected $id;

  /**
   * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Subscriber", inversedBy="subscriptions")
   * @ORM\JoinColumn(nullable=false)
   * @Serializer\Exclude()
   * @SWG\Property(description="Subscription's subscriber")
   */
  private $subscriber;

  /**
   * @ORM\OneToMany(targetEntity="AppBundle\Entity\SubscriptionPayment", mappedBy="subscription")
   * @Serializer\Exclude()
   * @SWG\Property(description="Subscription Payments")
   */
  private $subscriptionPayments;

  /**
   * @ORM\ManyToOne(targetEntity="AppBundle\Entity\SubscriptionService", inversedBy="subscriptions")
   * @Serializer\Exclude()
   * @ORM\JoinColumn(nullable=false)
   * @SWG\Property(description="Subscription's Subscription Service")
   */
  private $subscription_service;

  /**
   * @ORM\Column(type="json", options={"jsonb":true}, nullable=true)
   * @var $relatedCFs array
   */
  private $relatedCFs;

  /**
   * @ORM\Column(type="datetime", options={"default"="CURRENT_TIMESTAMP"})
   * @SWG\Property(description="Subscription's creation date")
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
   * @SWG\Property(description="Subscription's subscriber")
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
   * @SWG\Property(description="Subscription's subscription service")
   */
  public function getSubscriptionServiceId()
  {
    return $this->subscription_service->getId();
  }
}

<?php

namespace App\Entity;

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
   * @ORM\ManyToOne(targetEntity="App\Entity\Subscriber", inversedBy="subscriptions")
   * @ORM\JoinColumn(nullable=false)
   * @SWG\Property(description="Subscription's subscriber")
   */
  private $subscriber;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\SubscriptionService", inversedBy="subscriptions")
   * @Serializer\Exclude()
   * @ORM\JoinColumn(nullable=false)
   * @SWG\Property(description="Subscription's Subscription Service")
   */
  private $subscription_service;

  /**
   * @ORM\Column(type="datetime", options={"default"="CURRENT_TIMESTAMP"})
   * @SWG\Property(description="Subscription's creation date")
   */
  private $created_at;

  public function __construct()
  {
    $this->id = Uuid::uuid4();
    $this->setCreatedAt(new \DateTime('now'));
  }

  /**
   * @return string
   */
  public function __toString()
  {
    return (string) $this->getCode();
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

  public function getSubscriptionService(): ?SubscriptionService
  {
    return $this->subscription_service;
  }

  public function setSubscriptionService(?SubscriptionService $subscriptionService): self
  {
    $this->subscription_service = $subscriptionService;

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
}

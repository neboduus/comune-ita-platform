<?php


namespace AppBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Swagger\Annotations as SWG;

/**
 * @ORM\Entity
 * @ORM\Table(name="subscription_payment")
 */
class SubscriptionPayment
{
  use TimestampableEntity;

  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   * @SWG\Property(description="Subscription's uuid")
   */
  protected $id;

  /**
   * @ORM\Column(type="string", length=255, nullable=false)
   * @SWG\Property(description="Subscription Payment's name")
   */
  private $name;

  /**
   * @ORM\Column(type="decimal", scale=2, nullable=false)
   * @SWG\Property(description="Subscription Payment's amount")
   */
  private $amount = 0.00;

  /**
   * @ORM\Column(type="datetime", nullable=true)
   * @SWG\Property(description="Subscription Payment creation date")
   */
  private $paymentDate;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   * @SWG\Property(description="Subscription Payment's external key")
   */
  private $externalKey;

  /**
   * @var string
   * @ORM\Column(type="text", nullable=true)
   * @SWG\Property(description="Subscription Payment's description, accepts html tags")
   */
  private $description;

  /**
   * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Subscription", inversedBy="subscriptionPayments")
   * @ORM\JoinColumn(nullable=false)
   * @SWG\Property(description="Subscription payment")
   */
  private $subscription;


  public function __construct()
  {
    $this->id = Uuid::uuid4();
  }

  /**
   * @return string
   */
  public function __toString()
  {
    return (string)$this->getName();
  }

  /**
   * @return UuidInterface
   */
  public function getId()
  {
    return $this->id;
  }

  public function getName()
  {
    return $this->name;
  }

  public function setName($name)
  {
    $this->name = $name;

    return $this;
  }

  /**
   * @param float $amount
   * @return SubscriptionPayment
   */
  public function setAmount($amount)
  {
    $this->amount = $amount;

    return $this;
  }

  /**
   * @return float
   */
  public function getAmount()
  {
    return $this->amount;
  }

  public function getPaymentDate()
  {
    return $this->paymentDate;
  }

  public function setPaymentDate($paymentDate)
  {
    $this->paymentDate = $paymentDate;

    return $this;
  }

  public function getExternalKey(): ?string
  {
    return $this->externalKey;
  }

  public function setExternalKey(string $externalKey): self
  {
    $this->externalKey = $externalKey;

    return $this;
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

  public function getSubscription(): ?Subscription
  {
    return $this->subscription;
  }

  public function setSubscription(?Subscription $subscription): self
  {
    $this->subscription = $subscription;

    return $this;
  }

}

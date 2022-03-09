<?php


namespace AppBundle\Model;

use AppBundle\Entity\Servizio;
use JMS\Serializer\Annotation as Serializer;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;

class SubscriptionPayment implements \JsonSerializable
{
  const TYPE_SUBSCRIPTION_FEE = "subscription_fee";
  const TYPE_ADDITIONAL_FEE = "additional_fee";
  const TYPE_OPTIONAL = "optional";

  /**
   * @var double
   * @Assert\GreaterThanOrEqual(0, message="L'importo del pagamento deve avere un valore positivo")
   * @SWG\Property(description="Payment amount", type="double")
   */
  private $amount;

  /**
   * @var string
   * @Assert\NotNull(message="La causale di pagamento è obbligatorio")
   * @Assert\NotBlank (message="La causale di pagamento non può essere vuota")
   * @SWG\Property(description="Payment reason", type="string")
   */
  private $paymentReason;

  /**
   * @var string
   * @Assert\NotNull(message="L'identificativo del pagamento è obbligatorio")
   * @Assert\NotBlank (message="L'identificativo del pagamento non può essere vuoto")
   * @SWG\Property(description="Payment identifier", type="string")
   */
  private $paymentIdentifier;


  /**
   * @var string
   * @SWG\Property(description="Subscription service code", type="string")
   */
  private $subscriptionServiceCode;

  /**
   * @var bool
   * @SWG\Property(description="Create draft application before due date?", type="boolean")
   */
  private $createDraft = true;

  /**
   * @var string
   * @SWG\Property(description="Payment type", type="string", enum={"subscription_fee", "additional_required", "additional_optional"})
   */
  private $type;

  /**
   * @var string
   * @Serializer\Exclude()
   */
  private $meta;


  /**
   * @var Servizio
   * @Assert\NotNull(message="Il servizio di pagamento è obbligatorio")
   * @Assert\NotBlank (message="Il servizio di pagamento non può essere vuoto")
   */
  private $paymentService;

  /**
   * @var \DateTime
   */
  private $date;

  public function getAmount()
  {
    return $this->amount;
  }

  public function setAmount($amount)
  {
    $this->amount = $amount;
  }

  public function getPaymentReason()
  {
    return $this->paymentReason;
  }

  public function setPaymentReason($paymentReason)
  {
    $this->paymentReason = $paymentReason;
  }

  public function getPaymentIdentifier()
  {
    return $this->paymentIdentifier;
  }

  public function setPaymentIdentifier($paymentIdentifier)
  {
    $this->paymentIdentifier = $paymentIdentifier;
  }

  public function getSubscriptionServiceCode()
  {
    return $this->subscriptionServiceCode;
  }

  public function setSubscriptionServiceCode($subscriptionServiceCode)
  {
    $this->subscriptionServiceCode = $subscriptionServiceCode;
  }

  public function getPaymentService()
  {
    return $this->paymentService;
  }

  public function setPaymentService($paymentService)
  {
    $this->paymentService = $paymentService;
  }

  public function isRequired()
  {
    return  $this->getType() === self::TYPE_ADDITIONAL_FEE;
  }

  public function isSubscriptionFee()
  {
    return $this->getType() === self::TYPE_SUBSCRIPTION_FEE;
  }

  public function getCreateDraft()
  {
    return $this->createDraft;
  }

  public function setCreateDraft($createDraft)
  {
    $this->createDraft = $createDraft;
  }

  public function getDate()
  {
    return $this->date;
  }

  public function setDate($date)
  {
    $this->date = $date;
  }

  public function getMeta()
  {
    return $this->meta;
  }

  public function setMeta($meta)
  {
    $this->meta = $meta;
  }

  public function getType()
  {
    return $this->type;
  }

  public function setType($type)
  {
    $this->type = $type;
  }



  /**
   * @Serializer\VirtualProperty(name="meta")
   * @Serializer\Type("array<string, string>")
   * @Serializer\SerializedName("meta")
   * @SWG\Property(description="Payment metadata", type="array", @SWG\Items(type="object"))
   */
  public function getMetaAsArray(): array
  {
    return json_decode($this->meta, true);
  }

  public function jsonSerialize()
  {
    return array(
      'date' => $this->date->format(\DateTime::ATOM),
      'amount'=> $this->amount,
      'payment_reason'=> $this->paymentReason,
      'payment_identifier'=> $this->paymentIdentifier,
      'payment_service'=> $this->paymentService,
      'meta'=> $this->meta,
      'code' => $this->subscriptionServiceCode,
      'create_draft' => $this->createDraft,
      'type' => $this->type
    );
  }

}

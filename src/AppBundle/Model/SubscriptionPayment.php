<?php


namespace AppBundle\Model;

use AppBundle\Entity\Servizio;
use Symfony\Component\Validator\Constraints as Assert;

class SubscriptionPayment implements \JsonSerializable
{
  /**
   * @var double
   * @Assert\GreaterThanOrEqual(0, message="L'importo del pagamento deve avere un valore positivo")
   */
  private $amount;

  /**
   * @var string
   * @Assert\NotNull(message="La causale di pagamento è obbligatorio")
   * @Assert\NotBlank (message="La causale di pagamento non può essere vuota")
   */
  private $paymentReason;

  /**
   * @var string
   * @Assert\NotNull(message="L'identificativo del pagamento è obbligatorio")
   * @Assert\NotBlank (message="L'identificativo del pagamento non può essere vuoto")
   */
  private $paymentIdentifier;


  /**
   * @var string
   */
  private $subscriptionServiceCode;

  /**
   * @var string
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

  public function jsonSerialize()
  {
    return array(
      'date' => $this->date->format(\DateTime::ATOM),
      'amount'=> $this->amount,
      'payment_reason'=> $this->paymentReason,
      'payment_identifier'=> $this->paymentIdentifier,
      'payment_service'=> $this->paymentService,
      'meta'=> $this->meta,
      'code' => $this->subscriptionServiceCode
    );
  }

}

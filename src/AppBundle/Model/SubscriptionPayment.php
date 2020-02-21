<?php


namespace AppBundle\Model;

use Symfony\Component\Validator\Constraints as Assert;
use DateTime;

class SubscriptionPayment implements \JsonSerializable
{
  /**
   * @var double
   * @Assert\GreaterThanOrEqual(0, message="Questo campo deve avere un valore positivo")
   */
  private $amount;

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

  public function getDate()
  {
    return $this->date;
  }

  public function setDate($date)
  {
    $this->date = $date;
  }

  public function jsonSerialize()
  {
    return array(
      'date' => $this->date->format(\DateTime::ATOM),
      'amount'=> $this->amount,
    );
  }

}

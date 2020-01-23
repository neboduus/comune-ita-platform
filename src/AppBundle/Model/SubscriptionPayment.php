<?php


namespace AppBundle\Model;


class SubscriptionPayment implements \JsonSerializable
{
  /**
   * @var double
   */
  private $amount;

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
    return get_object_vars($this);
  }

}

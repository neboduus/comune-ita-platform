<?php


namespace App\Model;


use App\Entity\Subscriber;

class SubscriberMessage
{
  /**
   * @var Subscriber
   */
  private $subscriber;

  private $subject;

  private $message;

  /**
   * @var bool
   */
  private $auto_send = false;

  public function getSubscriber()
  {
    return $this->subscriber;
  }

  public function setSubscriber(Subscriber $subscriber)
  {
    $this->subscriber = $subscriber;
  }


  public function getSubject()
  {
    return $this->subject;
  }

  public function setSubject($subject)
  {
    $this->subject = $subject;
  }

  public function getMessage()
  {
    return $this->message;
  }

  public function setMessage($message)
  {
    $this->message = $message;
  }

  public function getFullName()
  {
    return $this->subscriber->getName().' '.$this->subscriber->getSurname();
  }

  public function getAutoSend()
  {
    return $this->auto_send;
  }

  public function setAutoSend(bool $autoSend)
  {
    $this->auto_send = $autoSend;
  }

}

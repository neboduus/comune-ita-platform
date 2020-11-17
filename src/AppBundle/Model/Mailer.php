<?php


namespace AppBundle\Model;


class Mailer implements \JsonSerializable
{
  private $title;

  private $transport = 'smtp';

  private $host;

  private $port;

  private $user;

  private $password;

  private $encription;

  private $sender;

  /**
   * @return mixed
   */
  public function getTitle()
  {
    return $this->title;
  }

  /**
   * @param mixed $title
   */
  public function setTitle($title): void
  {
    $this->title = $title;
  }

  /**
   * @return mixed
   */
  public function getTransport()
  {
    return $this->transport;
  }

  /**
   * @param mixed $transport
   */
  public function setTransport($transport): void
  {
    $this->transport = $transport;
  }

  /**
   * @return mixed
   */
  public function getHost()
  {
    return $this->host;
  }

  /**
   * @param mixed $host
   */
  public function setHost($host): void
  {
    $this->host = $host;
  }

  /**
   * @return mixed
   */
  public function getPort()
  {
    return $this->port;
  }

  /**
   * @param mixed $port
   */
  public function setPort($port): void
  {
    $this->port = $port;
  }

  /**
   * @return mixed
   */
  public function getUser()
  {
    return $this->user;
  }

  /**
   * @param mixed $user
   */
  public function setUser($user): void
  {
    $this->user = $user;
  }

  /**
   * @return mixed
   */
  public function getPassword()
  {
    return $this->password;
  }

  /**
   * @param mixed $password
   */
  public function setPassword($password): void
  {
    $this->password = $password;
  }

  /**
   * @return mixed
   */
  public function getEncription()
  {
    return $this->encription;
  }

  /**
   * @param mixed $encription
   */
  public function setEncription($encription): void
  {
    $this->encription = $encription;
  }

  /**
   * @return mixed
   */
  public function getSender()
  {
    return $this->sender;
  }

  /**
   * @param mixed $sender
   */
  public function setSender($sender): void
  {
    $this->sender = $sender;
  }

  /**
   * @return array|mixed
   */
  public function jsonSerialize()
  {
    return get_object_vars($this);
  }

  public function getIdentifier()
  {
    return \md5($this->title);
  }

  public static function fromArray($data = [])
  {
    $mailer = new Mailer();
    $mailer->setTitle($data['title'] ?? null);
    $mailer->setHost($data['host'] ?? null);
    $mailer->setPort($data['port'] ?? null);
    $mailer->setUser($data['user'] ?? null);
    $mailer->setPassword($data['password'] ?? null);
    $mailer->setEncription($data['encription'] ?? null);
    $mailer->setSender($data['sender'] ?? null);

    return $mailer;
  }

}

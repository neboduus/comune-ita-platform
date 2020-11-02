<?php

namespace AppBundle\Dto;

class UserAuthenticationData implements \JsonSerializable, \ArrayAccess, \Countable, \Iterator
{
  /**
   * @var string
   */
  private $authenticationMethod;

  private $sessionId;

  private $spidCode;

  private $spidLevel;

  private $certificateIssuer;

  private $certificateSubject;

  private $certificate;

  private $instant;

  private $sessionIndex;

  private $data = [];

  private function __construct(array $data)
  {
    foreach ($data as $key => $value){
      if (property_exists($this, $key) && !empty($value) && $key !== 'data'){
        $this->{$key} = $value;
        $this->data[$key] = $value;
      }
    }
  }

  /**
   * @param array $data
   * @return UserAuthenticationData
   */
  public static function fromArray(array $data)
  {
    return new static($data);
  }

  /**
   * @return string
   */
  public function getAuthenticationMethod(): string
  {
    return $this->authenticationMethod;
  }

  public function jsonSerialize()
  {
    return $this->data;
  }

  public function offsetExists($offset)
  {
    return isset($data[$offset]);
  }

  public function offsetGet($offset)
  {
    return $this->data[$offset];
  }

  public function offsetSet($offset, $value)
  {
    throw new \BadMethodCallException("UserAuthenticationData direct value assignment not allowed");
  }

  public function offsetUnset($offset)
  {
    throw new \BadMethodCallException("UserAuthenticationData unset not allowed");
  }

  public function count()
  {
    return count($this->data);
  }

  public function current()
  {
    return current($this->data);
  }

  public function next()
  {
    return next($this->data);
  }

  public function key()
  {
    return key($this->data);
  }

  public function valid()
  {
    $key = key($this->data);
    return ($key !== null && $key !== false);
  }

  public function rewind()
  {
    reset($this->data);
  }

}

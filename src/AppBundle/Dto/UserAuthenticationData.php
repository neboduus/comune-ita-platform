<?php

namespace AppBundle\Dto;

use Swagger\Annotations as SWG;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class UserAuthenticationData
 * @package AppBundle\Dto
 * @ExclusionPolicy("all")
 */
class UserAuthenticationData implements \JsonSerializable, \ArrayAccess, \Countable, \Iterator
{
  /**
   * @var string
   * @Serializer\Type("string")
   * @SWG\Property(description="Autentication method")
   * @Groups({"read"})
   * @Expose
   */
  private $authenticationMethod;

  /**
   * @var string
   * @Serializer\Type("string")
   * @SWG\Property(description="Autentication session id")
   * @Groups({"read"})
   * @Expose
   */
  private $sessionId;

  /**
   * @var string
   * @Serializer\Type("string")
   * @SWG\Property(description="Autentication spid code")
   * @Groups({"read"})
   * @Expose
   */
  private $spidCode;

  /**
   * @var string
   * @Serializer\Type("string")
   * @SWG\Property(description="Autentication session level")
   * @Groups({"read"})
   * @Expose
   */
  private $spidLevel;

  /**
   * @var string
   * @Serializer\Type("string")
   * @SWG\Property(description="Autentication certificate issuer")
   * @Groups({"read"})
   * @Expose
   */
  private $certificateIssuer;

  /**
   * @var string
   * @Serializer\Type("string")
   * @SWG\Property(description="Autentication certificate subject")
   * @Groups({"read"})
   * @Expose
   */
  private $certificateSubject;

  /**
   * @var string
   * @Serializer\Type("string")
   * @SWG\Property(description="Autentication certificate")
   * @Groups({"read"})
   * @Expose
   */
  private $certificate;

  /**
   * @var string
   * @Serializer\Type("string")
   * @SWG\Property(description="Autentication instant")
   * @Groups({"read"})
   * @Expose
   */
  private $instant;

  /**
   * @var string
   * @Serializer\Type("string")
   * @SWG\Property(description="Autentication session index")
   * @Groups({"read"})
   * @Expose
   */
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
  public function getAuthenticationMethod(): ?string
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
    $this->data[$offset] = $value;
    //throw new \BadMethodCallException("UserAuthenticationData direct value assignment not allowed");
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

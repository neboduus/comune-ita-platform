<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

class PostalAddress implements \JsonSerializable
{
  /**
   * @Serializer\Type("string")
   * @OA\Property(description="The country. For example, USA.")
   * @Groups({"read","write"})
   */
  private $addressCountry;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="The locality in which the street address is, and which is in the region. For example, Mountain View.")
   * @Groups({"read","write"})
   */
  private $addressLocality;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="The region in which the locality is, and which is in the country. For example, California or another appropriate first-level Administrative division.")
   * @Groups({"read","write"})
   */
  private $addressRegion;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="The post office box number for PO box addresses.")
   * @Groups({"read","write"})
   */
  private $postOfficeBoxNumber;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="The postal code. For example, 94043.")
   * @Groups({"read","write"})
   */
  private $postalCode;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="The street address. For example, 1600 Amphitheatre Pkwy.")
   * @Groups({"read","write"})
   */
  private $streetAddress;

  public function jsonSerialize()
  {
    return get_object_vars($this);
  }

  /**
   * @return mixed
   */
  public function getAddressCountry()
  {
    return $this->addressCountry;
  }

  /**
   * @param mixed $addressCountry
   */
  public function setAddressCountry($addressCountry): void
  {
    $this->addressCountry = $addressCountry;
  }

  /**
   * @return mixed
   */
  public function getAddressLocality()
  {
    return $this->addressLocality;
  }

  /**
   * @param mixed $addressLocality
   */
  public function setAddressLocality($addressLocality): void
  {
    $this->addressLocality = $addressLocality;
  }

  /**
   * @return mixed
   */
  public function getAddressRegion()
  {
    return $this->addressRegion;
  }

  /**
   * @param mixed $addressRegion
   */
  public function setAddressRegion($addressRegion): void
  {
    $this->addressRegion = $addressRegion;
  }

  /**
   * @return mixed
   */
  public function getPostOfficeBoxNumber()
  {
    return $this->postOfficeBoxNumber;
  }

  /**
   * @param mixed $postOfficeBoxNumber
   */
  public function setPostOfficeBoxNumber($postOfficeBoxNumber): void
  {
    $this->postOfficeBoxNumber = $postOfficeBoxNumber;
  }

  /**
   * @return mixed
   */
  public function getPostalCode()
  {
    return $this->postalCode;
  }

  /**
   * @param mixed $postalCode
   */
  public function setPostalCode($postalCode): void
  {
    $this->postalCode = $postalCode;
  }

  /**
   * @return mixed
   */
  public function getStreetAddress()
  {
    return $this->streetAddress;
  }

  /**
   * @param mixed $streetAddress
   */
  public function setStreetAddress($streetAddress): void
  {
    $this->streetAddress = $streetAddress;
  }

}

<?php

namespace App\Model\Security;

class Origin
{
  private ?string $ip;

  private ?string $ipDecimal;

  private ?string $country;

  private ?string $countryIso;

  private bool $countryEu;

  private ?string $region;

  private ?string $regionCode;

  private ?string $city;

  private ?string $timeZone;

  private ?string $asn;

  private ?string $asnOrg;

  /**
   * @return string
   */
  public function getIp(): ?string
  {
    return $this->ip;
  }

  /**
   * @param string $ip
   */
  public function setIp(?string $ip): void
  {
    $this->ip = $ip;
  }

  /**
   * @return string
   */
  public function getIpDecimal(): ?string
  {
    return $this->ipDecimal;
  }

  /**
   * @param string $ipDecimal
   */
  public function setIpDecimal(?string $ipDecimal): void
  {
    $this->ipDecimal = $ipDecimal;
  }

  /**
   * @return string
   */
  public function getCountry(): ?string
  {
    return $this->country;
  }

  /**
   * @param string $country
   */
  public function setCountry(?string $country): void
  {
    $this->country = $country;
  }

  /**
   * @return string
   */
  public function getCountryIso(): ?string
  {
    return $this->countryIso;
  }

  /**
   * @param string $countryIso
   */
  public function setCountryIso(?string $countryIso): void
  {
    $this->countryIso = $countryIso;
  }

  /**
   * @return bool
   */
  public function isCountryEu(): bool
  {
    return $this->countryEu;
  }

  /**
   * @param bool $countryEu
   */
  public function setCountryEu(bool $countryEu): void
  {
    $this->countryEu = $countryEu;
  }

  /**
   * @return string
   */
  public function getRegion(): ?string
  {
    return $this->region;
  }

  /**
   * @param string $region
   */
  public function setRegion(?string $region): void
  {
    $this->region = $region;
  }

  /**
   * @return string
   */
  public function getRegionCode(): ?string
  {
    return $this->regionCode;
  }

  /**
   * @param string $regionCode
   */
  public function setRegionCode(?string $regionCode): void
  {
    $this->regionCode = $regionCode;
  }

  /**
   * @return string
   */
  public function getCity(): ?string
  {
    return $this->city;
  }

  /**
   * @param string $city
   */
  public function setCity(?string $city): void
  {
    $this->city = $city;
  }

  /**
   * @return string
   */
  public function getTimeZone(): ?string
  {
    return $this->timeZone;
  }

  /**
   * @param string $timeZone
   */
  public function setTimeZone(?string $timeZone): void
  {
    $this->timeZone = $timeZone;
  }

  /**
   * @return string
   */
  public function getAsn(): ?string
  {
    return $this->asn;
  }

  /**
   * @param string $asn
   */
  public function setAsn(?string $asn): void
  {
    $this->asn = $asn;
  }

  /**
   * @return string
   */
  public function getAsnOrg(): ?string
  {
    return $this->asnOrg;
  }

  /**
   * @param string $asnOrg
   */
  public function setAsnOrg(?string $asnOrg): void
  {
    $this->asnOrg = $asnOrg;
  }

}

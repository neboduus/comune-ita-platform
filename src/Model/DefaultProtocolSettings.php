<?php


namespace App\Model;


class DefaultProtocolSettings
{
  const KEY = 'default_settings';

  /** @var string */
  private $certificate;

  /** @var string */
  private $certificateKey;

  /** @var string */
  private $certificatePassword;

  /**
   * @return string
   */
  public function getCertificate(): ?string
  {
    return $this->certificate;
  }

  /**
   * @param string $certificate
   */
  public function setCertificate(?string $certificate): void
  {
    $this->certificate = $certificate;
  }

  /**
   * @return string
   */
  public function getCertificateKey(): ?string
  {
    return $this->certificateKey;
  }

  /**
   * @param string $certificateKey
   */
  public function setCertificateKey(?string $certificateKey): void
  {
    $this->certificateKey = $certificateKey;
  }

  /**
   * @return string
   */
  public function getCertificatePassword(): ?string
  {
    return $this->certificatePassword;
  }

  /**
   * @param string $certificatePassword
   */
  public function setCertificatePassword(?string $certificatePassword): void
  {
    $this->certificatePassword = $certificatePassword;
  }

  public static function fromArray($data = [])
  {
    $settings = new DefaultProtocolSettings();
    $settings->setCertificate( $data['certificate'] ?? null);
    $settings->setCertificateKey( $data['certificateKey'] ?? null);
    $settings->setCertificatePassword( $data['certificatePassword'] ?? null);

    return $settings;
  }
}

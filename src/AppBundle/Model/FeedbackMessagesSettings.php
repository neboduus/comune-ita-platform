<?php


namespace AppBundle\Model;


class FeedbackMessagesSettings
{
  const KEY = 'feedback_messages_settings';

  private $pecMailer;

  private $pecReceiver;

  /**
   * @return mixed
   */
  public function getPecMailer()
  {
    return $this->pecMailer;
  }

  /**
   * @param mixed $pecMailer
   */
  public function setPecMailer($pecMailer): void
  {
    $this->pecMailer = $pecMailer;
  }

  /**
   * @return mixed
   */
  public function getPecReceiver()
  {
    return $this->pecReceiver;
  }

  /**
   * @param mixed $pecReceiver
   */
  public function setPecReceiver($pecReceiver): void
  {
    $this->pecReceiver = $pecReceiver;
  }

  public static function fromArray($data = [])
  {
    $settings = new FeedbackMessagesSettings();
    if (is_array($data)) {
      $settings->setPecMailer( $data['pec_mailer'] ?? null);
      $settings->setPecReceiver( $data['pec_receiver'] ?? null);
    }

    return $settings;
  }

}

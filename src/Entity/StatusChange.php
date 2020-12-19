<?php


namespace App\Entity;

use JMS\Serializer\Annotation as Serializer;
use Swagger\Annotations as SWG;

/**
 * Class StatusChange
 */
class StatusChange
{
  private $timestamp;
  private $evento;
  private $operatore;
  private $responsabile;
  private $struttura;
  private $message;

  /**
   * StatusChange constructor.
   * @param array $data
   */
  public function __construct($data = [])
  {

    $this->evento = $data['evento'] ?? null;
    $this->operatore = $data['operatore'] ?? null;
    $this->responsabile = $data['responsabile'] ?? null;
    $this->struttura = $data['struttura'] ?? null;
    $this->timestamp = $data['timestamp'] ?? $data['time'] ?? null;

    $this->message = $data['message'] ?? null;

    if (!is_int($this->timestamp)) {
      try {
        $date = new \DateTime($this->timestamp, new \DateTimeZone('Europe/Rome'));
      } catch (\Exception $e) {
        $date = new \DateTime();
      }
      $this->timestamp = $date->getTimestamp();
    }
  }

  /**
   * @return int
   */
  public function getTimestamp(): int
  {
    return $this->timestamp;
  }

  /**
   * @param int $timestamp
   */
  public function setTimestamp(int $timestamp)
  {
    $this->timestamp = $timestamp;
  }

  /**
   * @return mixed
   */
  public function getEvento()
  {
    return $this->evento;
  }

  /**
   * @param mixed $evento
   */
  public function setEvento($evento)
  {
    $this->evento = $evento;
  }

  /**
   * @return mixed
   */
  public function getOperatore()
  {
    return $this->operatore;
  }

  /**
   * @param mixed $operatore
   */
  public function setOperatore($operatore)
  {
    $this->operatore = $operatore;
  }

  /**
   * @return mixed
   */
  public function getResponsabile()
  {
    return $this->responsabile;
  }

  /**
   * @param mixed $responsabile
   */
  public function setResponsabile($responsabile)
  {
    $this->responsabile = $responsabile;
  }

  /**
   * @return mixed
   */
  public function getStruttura()
  {
    return $this->struttura;
  }

  /**
   * @param mixed $struttura
   */
  public function setStruttura($struttura)
  {
    $this->struttura = $struttura;
  }

  /**
   * @return mixed|null
   */
  public function getMessage()
  {
    return $this->message;
  }

  /**
   * @param string|null $message
   */
  public function setMessage($message)
  {
    $this->message = $message;
  }


  /**
   * @return string
   */
  public function toJson()
  {
    return json_encode(
      $this->toArray()
    );
  }

  /**
   * @return array
   */
  public function toArray()
  {
    return [
      'evento' => $this->evento,
      'operatore' => $this->operatore,
      'responsabile' => $this->responsabile,
      'struttura' => $this->struttura,
      'timestamp' => $this->timestamp,
      'message' => $this->message,
    ];
  }
}

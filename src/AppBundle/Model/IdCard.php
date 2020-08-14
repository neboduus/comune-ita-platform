<?php


namespace AppBundle\Model;


class IdCard implements \JsonSerializable
{

  /**
   * @var string
   */
  private $numero;

  /**
   * @var string
   */
  private $comuneRilascio;

  /**
   * @var \DateTime
   */
  private $dataRilascio;

  /**
   * @var \DateTime
   */
  private $dataScadenza;

  /**
   * @return string
   */
  public function getNumero(): string
  {
    return $this->numero;
  }

  /**
   * @param string $numero
   */
  public function setNumero(string $numero)
  {
    $this->numero = $numero;
  }

  /**
   * @return string
   */
  public function getComuneRilascio(): string
  {
    return $this->comuneRilascio;
  }

  /**
   * @param string $comuneRilascio
   */
  public function setComuneRilascio(string $comuneRilascio)
  {
    $this->comuneRilascio = $comuneRilascio;
  }

  /**
   * @param \DateTime $dataRilascio
   */
  public function setDataRilascio($dataRilascio)
  {
    $this->dataRilascio = $dataRilascio;
  }

  /**
   * @return \DateTime
   */
  public function getDataRilascio()
  {
    return $this->dataRilascio;
  }

  /**
   * @param \DateTime $dataScadenza
   */
  public function setDataScadenza($dataScadenza)
  {
    $this->dataScadenza = $dataScadenza;
  }

  /**
   * @return \DateTime
   */
  public function getDataScadenza()
  {
    return $this->dataScadenza;
  }


  public function jsonSerialize()
  {
    return array(
      'numero' => $this->numero,
      'comune_rilascio' => $this->comuneRilascio,
      'data_rilascio' => $this->dataRilascio->format('d/m/Y'),
      'data_scadenza'=> $this->dataScadenza->format('d/m/Y'),
    );
  }

}

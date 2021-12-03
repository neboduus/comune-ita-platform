<?php


namespace AppBundle\Model;


use JMS\Serializer\Annotation as Serializer;
use Swagger\Annotations as SWG;

class IdCard implements \JsonSerializable
{

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @SWG\Property(description="Id card's number")
   */
  private $numero;

  /**
   * @var string
   *
   *
   * @Serializer\Type("string")
   * @SWG\Property(description="Id card's city")
   */
  private $comuneRilascio;

  /**
   * @var \DateTime
   *
   * @Serializer\Type("DateTime")
   * @SWG\Property(description="Id card's date")
   */
  private $dataRilascio;

  /**
   * @var \DateTime
   *
   * @Serializer\Type("DateTime")
   * @SWG\Property(description="Id card's expire date")
   */
  private $dataScadenza;

  /**
   * @return string
   */
  public function getNumero()
  {
    return $this->numero;
  }

  /**
   * @param string $numero
   */
  public function setNumero($numero)
  {
    $this->numero = $numero;
  }

  /**
   * @return string
   */
  public function getComuneRilascio()
  {
    return $this->comuneRilascio;
  }

  /**
   * @param string $comuneRilascio
   */
  public function setComuneRilascio($comuneRilascio)
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
    if ($this->dataRilascio instanceof \DateTime) {
      return $this->dataRilascio;
    }
    return null;
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
    if ($this->dataScadenza instanceof \DateTime) {
      return $this->dataScadenza;
    }
    return null;
  }


  public function jsonSerialize()
  {
    return array(
      'numero' => $this->numero,
      'comune_rilascio' => $this->comuneRilascio,
      'data_rilascio' => $this->dataRilascio ? $this->dataRilascio->format('d/m/Y') : '',
      'data_scadenza'=> $this->dataScadenza ? $this->dataScadenza->format('d/m/Y') : '',
    );
  }

}

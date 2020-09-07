<?php


namespace AppBundle\Model;

use AppBundle\Model\Gateway;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;


class PaymentOutcome implements \JsonSerializable
{

  /*
   {
    "status": "OK|KO"
    "status_code": "PAA_SYSTEM_ERROR|PAYMENT_NON_SO_CHE_ALTRO",
    "status_message": "Pagamento effettuato",
    "data": [ dati relativi al pagamento restituiti da MyPay ]
    }
   */


  /**
   * @var string
   * @Serializer\Type("string")
   * @Assert\NotBlank(message="This field is mandatory: status")
   * @Assert\NotNull(message="This field is mandatory: status")
   * @SWG\Property(description="Payment status")
   */
  private $status;

  /**
   * @var string
   * @Serializer\Type("string")
   * @Assert\NotBlank(message="This field is mandatory: status_code")
   * @Assert\NotNull(message="This field is mandatory: status_code")
   * @SWG\Property(description="Payment status code")
   */
  private $statusCode;

  /**
   * @var string
   * @Serializer\Type("string")
   * @Assert\NotBlank(message="This field is mandatory: status_message")
   * @Assert\NotNull(message="This field is mandatory: status_message")
   * @SWG\Property(description="Payment status message")
   */
  private $statusMessage;

  /**
   * @var array
   * @Serializer\Type("array")
   * @SWG\Property(description="Payment data")
   *
   */
  private $data;

  /**
   * PaymentOutcome constructor.
   * @param array $data
   */
  public function __construct()
  {
    $this->data = array();
  }

  /**
   * @return string
   */
  public function getStatus()
  {
    return $this->status;
  }

  /**
   * @param string $status
   */
  public function setStatus(string $status)
  {
    $this->status = $status;
  }

  /**
   * @return string
   */
  public function getStatusCode()
  {
    return $this->statusCode;
  }

  /**
   * @param string $statusCode
   */
  public function setStatusCode(string $statusCode)
  {
    $this->statusCode = $statusCode;
  }

  /**
   * @return string
   */
  public function getStatusMessage()
  {
    return $this->statusMessage;
  }

  /**
   * @param string $statusMessage
   */
  public function setStatusMessage(string $statusMessage)
  {
    $this->statusMessage = $statusMessage;
  }

  /**
   * @return array
   */
  public function getData()
  {
    return $this->data;
  }

  /**
   * @param array
   */
  public function setData($data)
  {
    if (!is_array($data)) {
      $data = json_decode($data, true);
    }
    $this->data = $data;
  }

  public function jsonSerialize()
  {
    return get_object_vars($this);
  }


}

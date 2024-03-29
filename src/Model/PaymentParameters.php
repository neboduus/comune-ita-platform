<?php


namespace App\Model;

use App\Model\Gateway;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;


class PaymentParameters
{

  // {"datiSpecificiRiscossione":"","codIpaEnte":"","password":"","importo":""}

  /**
   * @var double
   * @Serializer\Type("double")
   * @OA\Property(description="Service's cost")
   */
  private $totalAmounts;

  /**
   * @var Gateway[]
   * @OA\Property(property="gateways", type="array", @OA\Items(ref=@Model(type=Gateway::class)))
   */
  private $gateways;


  public function __construct()
  {
    $this->gateways = new ArrayCollection();
  }

  /**
   * @return float
   */
  public function getTotalAmounts(): float
  {
    return $this->totalAmounts;
  }

  /**
   * @param float $totalAmounts
   */
  public function setTotalAmounts(float $totalAmounts)
  {
    $this->totalAmounts = $totalAmounts;
  }


  /*public function getGateways()
  {
    $gateways = [];
    if ( count($this->flowSteps) > 0) {
      foreach ($this->flowSteps as $v) {
        if (is_array($v)) {
          $gateways[] = $v;

        } else {
          $gateways[] = json_decode($v, true);
        }
      }
    }
    return $gateways;
  }


  public function setGateways(array $gateways)
  {
    $this->gateways = array_map(function (Gateway $gateway) {
      return json_encode($gateway);
    }, $gateways);
  }*/

}

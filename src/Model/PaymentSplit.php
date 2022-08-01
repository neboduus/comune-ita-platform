<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;

class PaymentSplit
{
  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Payment split code")
   * @Groups({"read"})
   */
  private $code;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Payment  split amount")
   * @Groups({"read"})
   */
  private $amount;

  /**
   * @var array
   * @SWG\Property(description="Payment split meta")
   * @Groups({"read"})
   * @Serializer\Type("array")
   */
  private $meta;
}

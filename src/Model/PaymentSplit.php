<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Model;

class PaymentSplit
{
  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Payment split code")
   * @Groups({"read"})
   */
  private $code;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Payment  split amount")
   * @Groups({"read"})
   */
  private $amount;

  /**
   * @var array
   * @OA\Property(description="Payment split meta")
   * @Groups({"read"})
   * @Serializer\Type("array")
   */
  private $meta;
}

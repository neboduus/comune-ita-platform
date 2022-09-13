<?php

namespace App\Model;

use DateTime;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Model;

class PaymentTransaction
{
  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Payment uuid")
   * @Groups({"read"})
   */
  private $transactionId;

  /**
   * @var DateTime
   * @Serializer\Type("DateTime")
   * @OA\Property(description="Created at date time")
   * @Groups({"read"})
   */
  private $paidAt;

  /**
   * @var DateTime
   * @Serializer\Type("DateTime")
   * @OA\Property(description="Created at date time")
   * @Groups({"read"})
   */
  private $expireAt;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Payment user id")
   * @Groups({"read"})
   */
  private $amount;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Payment user id")
   * @Groups({"read"})
   */
  private $currency;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Payment user id")
   * @Groups({"read"})
   */
  private $noticeCode;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Payment user id")
   * @Groups({"read"})
   */
  private $iud;

  /**
   * @Serializer\Type("string")
   * @OA\Property(description="Payment user id")
   * @Groups({"read"})
   */
  private $iuv;

  /**
   * @var PaymentSplit[]
   * @OA\Property(type="array", @OA\Items(ref=@Model(type=PaymentSplit::class, groups={"read"})))
   * @Serializer\Type("array")
   * @Groups({"read"})
   */
  private $split;

}

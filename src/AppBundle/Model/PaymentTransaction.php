<?php

namespace AppBundle\Model;

use DateTime;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;

class PaymentTransaction
{
  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Payment uuid")
   * @Groups({"read"})
   */
  private $transactionId;

  /**
   * @var DateTime
   * @Serializer\Type("DateTime")
   * @SWG\Property(description="Created at date time")
   * @Groups({"read"})
   */
  private $paidAt;

  /**
   * @var DateTime
   * @Serializer\Type("DateTime")
   * @SWG\Property(description="Created at date time")
   * @Groups({"read"})
   */
  private $expireAt;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Payment user id")
   * @Groups({"read"})
   */
  private $amount;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Payment user id")
   * @Groups({"read"})
   */
  private $currency;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Payment user id")
   * @Groups({"read"})
   */
  private $noticeCode;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Payment user id")
   * @Groups({"read"})
   */
  private $iud;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Payment user id")
   * @Groups({"read"})
   */
  private $iuv;

  /**
   * @var PaymentSplit[]
   * @SWG\Property(type="array", @SWG\Items(ref=@Model(type=PaymentSplit::class, groups={"read"})))
   * @Serializer\Type("array")
   * @Groups({"read"})
   */
  private $split;

}

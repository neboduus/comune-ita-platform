<?php

namespace App\Model;

use DateTime;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;

class Payment
{
  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Payment uuid")
   * @Groups({"read"})
   */
  private $id;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Payment user id")
   * @Groups({"read"})
   */
  private $userId;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Payment user id")
   * @Groups({"read"})
   */
  private $type;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Payment user id")
   * @Groups({"read"})
   */
  private $tenantId;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Payment user id")
   * @Groups({"read"})
   */
  private $serviceId;

  /**
   * @var DateTime
   * @Serializer\Type("DateTime")
   * @SWG\Property(description="Created at date time")
   * @Groups({"read"})
   */
  private $createdAt;

  /**
   * @var DateTime
   * @Serializer\Type("DateTime")
   * @SWG\Property(description="Created at date time")
   * @Groups({"read"})
   */
  private $updatedAt;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Payment user id")
   * @Groups({"read"})
   */
  private $status;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Payment user id")
   * @Groups({"read"})
   */
  private $reason;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Payment user id")
   * @Groups({"read"})
   */
  private $remoteId;

  /**
   * @var PaymentTransaction
   * @SWG\Property(type="object", ref=@Model(type=PaymentTransaction::class, groups={"read"}))
   * @Serializer\Type("array")
   * @Groups({"read"})
   */
  private $payment;

}

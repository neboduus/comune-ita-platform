<?php


namespace AppBundle\Dto;

use AppBundle\Entity\Calendar;
use AppBundle\Entity\OpeningHour;
use DateTime;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Swagger\Annotations as SWG;

class Meeting
{

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Meeting's uuid")
   * @Groups({"read"})
   */
  protected $id;

  /**
   * @SWG\Property(description="Meeting's calendar")
   * @Groups({"read"})
   */
  private $calendar;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Meeting's user ID (uuid)", format="uuid")
   * @Groups({"read"})
   */
  private $userId;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Meeting's application ID (uuid)", format="uuid")
   * @Groups({"read"})
   */
  private $applicationId;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Meeting's service ID (uuid)", format="uuid")
   * @Groups({"read"})
   */
  private $serviceId;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Meeting's user email", format="uuid")
   * @Groups({"read"})
   */
  private $email;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Meeting's user phone number")
   * @Groups({"read"})
   */
  private $phoneNumber;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Meeting's user fiscal code")
   * @Groups({"read"})
   */
  private $fiscalCode;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Meeting's user name")
   * @Groups({"read"})
   */
  private $name;


  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Meeting's tenant ID(uuid)", format="uuid")
   * @Groups({"read"})
   */
  private $tenantId;


  /**
   * @var OpeningHour
   * @SWG\Property(property="opening_hour", description="Meeting's Opening hour")
   * @Groups({"read"})
   */
  private $openingHour;


  /**
   * @Serializer\Type("DateTime")
   * @SWG\Property(description="Creation date time", type="dateTime")
   * @Groups({"read"})
   */
  private $createdAt;

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="Meeting status name")
   * @Groups({"read"})
   */
  private $statusName;

  /**
   * @Serializer\Type("array")
   * @SWG\Property(description="Application's session")
   * @Groups({"read"})
   */
  private $session;


  /**
   * @return mixed
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * @param mixed $id
   */
  public function setId($id)
  {
    $this->id = $id;
  }

  /**
   * @return Calendar
   */
  public function getCalendar()
  {
    return $this->calendar;
  }

  /**
   * @param Calendar $calendar
   */
  public function setCalendar(Calendar $calendar)
  {
    $this->calendar = $calendar;
  }

  /**
   * @return mixed
   */
  public function getUserId()
  {
    return $this->userId;
  }

  /**
   * @param mixed $userId
   */
  public function setUserId($userId)
  {
    $this->userId = $userId;
  }

  /**
   * @return mixed
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * @param mixed $name
   */
  public function setName($name)
  {
    $this->name = $name;
  }

  /**
   * @return mixed
   */
  public function getSurname()
  {
    return $this->surname;
  }

  /**
   * @param mixed $surname
   */
  public function setSurname($surname)
  {
    $this->surname = $surname;
  }

  /**
   * @return mixed
   */
  public function getFiscalCode()
  {
    return $this->fiscalCode;
  }

  /**
   * @param mixed $fiscalCode
   */
  public function setFiscalCode($fiscalCode)
  {
    $this->fiscalCode = $fiscalCode;
  }

  /**
   * @return mixed
   */
  public function getEmail()
  {
    return $this->email;
  }

  /**
   * @param mixed $email
   */
  public function setEmail($email)
  {
    $this->email = $email;
  }

  /**
   * @return mixed
   */
  public function getPhoneNumber()
  {
    return $this->phoneNumber;
  }

  /**
   * @param mixed $phoneNumber
   */
  public function setPhoneNumber($phoneNumber)
  {
    $this->phoneNumber = $phoneNumber;
  }

  /**
   * @return mixed
   */
  public function getApplicationId()
  {
    return $this->applicationId;
  }

  /**
   * @param mixed $applicationId
   */
  public function setApplicationId($applicationId)
  {
    $this->applicationId = $applicationId;
  }

  /**
   * @return mixed
   */
  public function getServiceId()
  {
    return $this->serviceId;
  }

  /**
   * @param mixed $serviceId
   */
  public function setServiceId($serviceId): void
  {
    $this->serviceId = $serviceId;
  }

  /**
   * @return mixed
   */
  public function getTenantId()
  {
    return $this->tenantId;
  }

  /**
   * @param mixed $tenantId
   */
  public function setTenantId($tenantId)
  {
    $this->tenantId = $tenantId;
  }


  /**
   * @return array
   */
  public function getOpeningHour(): ?array
  {
    return $this->openingHour;
  }

  /**
   * @param OpeningHour $openingHour
   */
  public function setOpeningHour(OpeningHour $openingHour)
  {
   $this->openingHour = $openingHour;
  }

  /**
   * @return DateTime
   */
  public function getCreatedAt()
  {
    return $this->createdAt;
  }

  /**
   * @param DateTime $createdAt
   */
  public function setCreatedAt(DateTime $createdAt)
  {
    $this->createdAt = $createdAt;
  }


  /**
   * @return mixed
   */
  public function getStatusName()
  {
    return $this->statusName;
  }

  /**
   * @param mixed $statusName
   */
  public function setStatusName($statusName)
  {
    $this->statusName = $statusName;
  }

  /**
   * @return mixed
   */
  public function getSession()
  {
    return $this->session;
  }

  /**
   * @param mixed $session
   */
  public function setSession($session)
  {
    $this->session = $session;
  }


}

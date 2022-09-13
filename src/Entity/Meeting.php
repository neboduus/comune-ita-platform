<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use JMS\Serializer\Annotation\Groups;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use OpenApi\Annotations as OA;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * Meeting
 *
 * @ORM\Entity(repositoryClass="App\Entity\MeetingRepository")
 * @ORM\Table(name="meeting")
 */
class Meeting
{

  use TimestampableEntity;

  const STATUS_PENDING = 0;
  const STATUS_APPROVED = 1;
  const STATUS_REFUSED = 2;
  const STATUS_MISSED = 3;
  const STATUS_DONE = 4;
  const STATUS_CANCELLED = 5;
  const STATUS_DRAFT = 6;

  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   * @OA\Property(description="Meeting's uuid", type="string")
   * @Groups({"read", "kafka"})
   */
  private $id;

  /**
 * @ORM\ManyToOne(targetEntity="App\Entity\Calendar")
 * @ORM\JoinColumn(name="calendar_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
 * @Assert\NotBlank(message="Questo campo è obbligatorio (calendar)")
 * @OA\Property(description="Meeting's calendar id", type="string")
 * @Groups({"kafka"})
 */
  private $calendar;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\OpeningHour", inversedBy="meetings")
   * @ORM\JoinColumn(name="opening_hour_id", referencedColumnName="id", nullable=true)
   * @OA\Property(description="Meeting's opening hour id", type="string")
   * @Groups({"kafka"})
   */
  private $openingHour;

  /**
   * @var string
   *
   * @ORM\Column(name="email", type="string", length=255, nullable=true)
   * @OA\Property(description="Meeting's user email", type="string")
   * @Groups({"read", "write", "kafka"})
   */
  private $email;

  /**
   * @var string
   *
   * @ORM\Column(name="phone_number", type="string", nullable=true)
   * @OA\Property(description="Meeting's user phone number", type="string")
   * @Groups({"read", "write", "kafka"})
   */
  private $phoneNumber;

  /**
   * @var string
   *
   * @ORM\Column(name="fiscal_code", type="string", length=16, nullable=true)
   * @OA\Property(description="Meeting's user fiscal code", type="string")
   * @Groups({"read", "write", "kafka"})
   */
  private $fiscalCode;

  /**
   * @var string
   *
   * @ORM\Column(name="name", type="string", length=255, nullable=true)
   * @OA\Property(description="Meeting's user name", type="string")
   * @Groups({"read", "write", "kafka"})
   */
  private $name;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\CPSUser")
   * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
   * @OA\Property(description="Meeting's user id", type="string")
   * @Serializer\Exclude()
   */
  private $user;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="from_time", type="datetime")
   * @Assert\NotBlank(message="Questo campo è obbligatorio (from Time)")
   * @OA\Property(description="Meeting's from Time")
   * @Groups({"read", "write", "kafka"})
   */
  private $fromTime;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="to_time", type="datetime")
   * @Assert\NotBlank(message="Questo campo è obbligatorio (to Time)")
   * @OA\Property(description="Meeting's to Time")
   * @Groups({"read", "write", "kafka"})
   */
  private $toTime;

  /**
   * @var string
   *
   * @ORM\Column(name="user_message", type="text", nullable=true)
   * @OA\Property(description="Meeting's User Message", type="string")
   * @Groups({"read", "write", "kafka"})
   */
  private $userMessage;

  /**
   * @var string
   *
   * @ORM\Column(name="motivation_outcome", type="text", nullable=true)
   * @OA\Property(description="Meeting's Operator Message", type="string")
   * @Groups({"read", "write", "kafka"})
   */
  private $motivationOutcome;

  /**
   * @Serializer\Exclude()
   * @ORM\ManyToMany(targetEntity="App\Entity\Pratica", mappedBy="meetings")
   */
  private $applications;

  /**
   * @var string
   *
   * @ORM\Column(name="videoconference_link", type="string", nullable=true)
   * @Assert\Url(message="url non valido (videoconferenceLink)")
   * @OA\Property(description="Meeting's videoconference link", type="string")
   * @Groups({"read", "write", "kafka"})
   */
  private $videoconferenceLink;

  /**
   * @ORM\Column(type="integer")
   * @Assert\NotBlank(message="Seleziona un'opzione. Lo stato è un parametro obbligatorio")
   * @Assert\NotNull()
   * @OA\Property(description="Meeting's status", type="integer")
   * @Groups({"read", "write"})
   */
  private $status;

  /**
   * @ORM\Column(type="integer", nullable=false)
   * @OA\Property(description="Meeting's rescheduled times", type="integer")
   * @Groups({"read", "write", "kafka"})
   */
  private $rescheduled;

  /**
   * @var string
   *
   * @ORM\Column(name="cancel_link", type="string", length=255, nullable=true)
   * @OA\Property(description="Meeting's cancel link", type="string")
   * @Serializer\Exclude()
   */
  private $cancelLink;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="draft_expiration", type="datetime", nullable=true)
   * @OA\Property(description="Meeting draft expiration time")
   * @Groups({"read", "write", "kafka"})
   */
  private $draftExpiration;

  /**
   * @var \DateTime
   * @ORM\Column(type="date", nullable=true)
   * @OA\Property(description="First available date when meeting was created, format Y-m-d")
   * @Serializer\Type("DateTime<'Y-m-d'>")
   * @Groups({"read", "write", "kafka"})
   */
  private $firstAvailableDate;

  /**
   * @var \DateTime
   * @ORM\Column(type="time", nullable=true)
   * @OA\Property(description="First available end time")
   * @Serializer\Exclude()
   */
  private $firstAvailableStartTime;

  /**
   * @var \DateTime
   * @ORM\Column(type="time", nullable=true)
   * @OA\Property(description="First available end time")
   * @Serializer\Exclude()
   */
  private $firstAvailableEndTime;

  /**
   * @var \DateTime
   * @ORM\Column(type="datetime", nullable=true)
   * @OA\Property(description="Datetime when first availability was stored")
   * @Groups({"read", "write", "kafka"})
   */
  private $firstAvailabilityUpdatedAt;

  /**
   * @var \DateTime
   * @Gedmo\Timestampable(on="create")
   * @ORM\Column(type="datetime")
   * @Groups({"read", "kafka"})
   */
  protected $createdAt;

  /**
   * @var \DateTime
   * @Gedmo\Timestampable(on="update")
   * @ORM\Column(type="datetime")
   * @Groups({"read", "kafka"})
   */
  protected $updatedAt;

  /**
   * Meeting constructor.
   * @throws \Exception
   */

  public function __construct()
  {
    if (!$this->id) {
      $this->id = Uuid::uuid4();
      $this->cancelLink = hash('sha256', $this->id . (new DateTime())->format('c'));
      $this->applications = new ArrayCollection();
    }
  }

  /**
   * get id
   *
   * @return UuidInterface
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Get Calendar
   *
   * @return Calendar
   */
  public function getCalendar(): ?Calendar
  {
    return $this->calendar;
  }

  /**
   * Set Calendar
   *
   * @param Calendar $calendar
   * @return $this
   */
  public function setCalendar(?Calendar $calendar): self
  {
    $this->calendar = $calendar;

    return $this;
  }

  /**
   * @Serializer\VirtualProperty()
   * @Serializer\Type("string")
   * @Serializer\SerializedName("calendar")
   * @Groups({"read", "write"})
   */
  public function getCalendarId(): string
  {
    return $this->calendar->getId();
  }

  /**
   * @Serializer\VirtualProperty()
   * @Serializer\Type("string")
   * @Serializer\SerializedName("application")
   * @Groups({"read", "write"})
   */
  public function getApplicationId(): ?string
  {
    $applicationId = null;
    if ($this->getApplications()->count() > 0) {
      $applicationId = $this->getApplications()->last()->getId();
    }
    return $applicationId;
  }

  /**
   * @Serializer\VirtualProperty()
   * @Serializer\Type("string")
   * @Serializer\SerializedName("application_id")
   * @Groups({"kafka"})
   */
  public function getApplicationIdKafka(): ?string
  {
    return $this->getApplicationId();
  }

  /**
   * @Serializer\VirtualProperty()
   * @Serializer\Type("string")
   * @Serializer\SerializedName("service_id")
   * @Groups({"kafka"})
   */
  public function getServiceId(): ?string
  {
    if ($this->getApplications()->count() > 0) {
      /** @var Pratica $application */
      $application = $this->getApplications()->last();
      return $application->getServizio()->getId();
    }
    return null;
  }

  /**
   * @Serializer\VirtualProperty()
   * @Serializer\Type("string")
   * @Serializer\SerializedName("user")
   * @Groups({"read", "write"})
   */
  public function getUserId(): ?string
  {
    if ($this->user){
      return $this->user->getId();
    }
    return null;
  }

  /**
   * @Serializer\VirtualProperty()
   * @Serializer\Type("string")
   * @Serializer\SerializedName("user_id")
   * @Groups({"kafka"})
   */
  public function getUserIdKafka(): ?string
  {
    if ($this->user){
      return $this->user->getId();
    }
    return null;
  }

  /**
   * @Serializer\VirtualProperty()
   * @Serializer\Type("string")
   * @Serializer\SerializedName("first_available_slot")
   * @Groups({"read", "write", "kafka"})
   */
  public function getFirstAvailableSlot()
  {
    if ($this->firstAvailableStartTime !== null && $this->firstAvailableEndTime !== null) {
      return $this->firstAvailableStartTime->format('H:i') . '-' . $this->firstAvailableEndTime->format('H:i');
    }
    return null;
  }

  /**
   * @Serializer\VirtualProperty()
   * @Serializer\Type("string")
   * @Serializer\SerializedName("status_name")
   * @Groups({"kafka"})
   */
  public function getStatusName()
  {
    $class = new \ReflectionClass(__CLASS__);
    $constants = $class->getConstants();
    foreach ($constants as $name => $value) {
      if ($value == $this->status) {
        return strtolower($name);
      }
    }
    return null;
  }

  /**
   * Get OpeningHour
   *
   * @return OpeningHour
   */
  public function getOpeningHour(): ?OpeningHour
  {
    return $this->openingHour;
  }

  /**
   * Set OpeningHour
   *
   * @param OpeningHour $openingHour
   * @return $this
   */
  public function setOpeningHour(?OpeningHour $openingHour): self
  {
    $this->openingHour = $openingHour;

    return $this;
  }

  /**
   * Set email.
   *
   * @param string $email
   *
   * @return Meeting
   */
  public function setEmail($email)
  {
    $this->email = $email;

    return $this;
  }

  /**
   * Get email.
   *
   * @return string|null
   */
  public function getEmail()
  {
    return $this->email;
  }

  /**
   * Set phone number.
   *
   * @param string $phoneNumber
   *
   * @return Meeting
   */
  public function setPhoneNumber($phoneNumber)
  {
    $this->phoneNumber = $phoneNumber;

    return $this;
  }

  /**
   * Get phone number.
   *
   * @return string|null
   */
  public function getPhoneNumber()
  {
    return $this->phoneNumber;
  }

  /**
   * Set user Fiscal Code.
   *
   * @param string $fiscal_code
   *
   * @return Meeting
   */
  public function setFiscalCode($fiscal_code)
  {
    $this->fiscalCode = $fiscal_code;

    return $this;
  }

  /**
   * Get fiscal code.
   *
   * @return string|null
   */
  public function getFiscalCode()
  {
    return $this->fiscalCode;
  }

  /**
   * Set user Name.
   *
   * @param string $name
   *
   * @return Meeting
   */
  public function setName($name)
  {
    $this->name = $name;

    return $this;
  }

  /**
   * Get name.
   *
   * @return string|null
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Get Owner
   *
   * @return CPSUser|null
   */
  public function getUser(): ?CPSUser
  {
    return $this->user;
  }

  /**
   * Get Owner
   *
   * @param CPSUser|null $user
   * @return $this
   */
  public function setUser(?CPSUser $user): self
  {
    if ($user)
      $this->user = $user;

    return $this;
  }

  /**
   * Set fromTime.
   *
   * @param \DateTime $fromTime
   *
   * @return Meeting
   */
  public function setFromTime($fromTime)
  {
    $this->fromTime = $fromTime;

    return $this;
  }

  /**
   * Get fromTime.
   *
   * @return \DateTime
   */
  public function getFromTime()
  {
    return $this->fromTime;
  }

  /**
   * Set toTime.
   *
   * @param \DateTime $toTime
   *
   * @return Meeting
   */
  public function setToTime($toTime)
  {
    $this->toTime = $toTime;

    return $this;
  }

  /**
   * Get toTime.
   *
   * @return \DateTime
   */
  public function getToTime()
  {
    return $this->toTime;
  }

  /**
   * Get status
   *
   * @return mixed
   */
  public function getStatus()
  {
    return $this->status;
  }

  /**
   * Set status
   *
   * @param $status
   *
   * @return $this
   */
  public function setStatus($status)
  {
    $this->status = $status;

    return $this;
  }

  /**
   * Get Applications
   *
   */
  public function getApplications()
  {
    return $this->applications;
  }

  /**
   * @param Pratica $application
   * @return $this
   */
  public function addApplication(Pratica $application)
  {
    if (!$this->applications->contains($application)) {
      $this->applications->add($application);
    }

    return $this;
  }


  /**
   * Set userMessage.
   *
   * @param string $userMessage
   *
   * @return Meeting
   */
  public function setUserMessage($userMessage)
  {
    $this->userMessage = $userMessage;

    return $this;
  }

  /**
   * Get userMessage.
   *
   * @return string
   */
  public function getUserMessage()
  {
    return $this->userMessage;
  }

  /**
   * Set motivationOutcome.
   *
   * @param string $motivationOutcome
   *
   * @return Meeting
   */
  public function setMotivationOutcome($motivationOutcome)
  {
    $this->motivationOutcome = $motivationOutcome;

    return $this;
  }

  /**
   * Get motivationOutcome.
   *
   * @return string
   */
  public function getMotivationOutcome()
  {
    return $this->motivationOutcome;
  }

  /**
   * Set videoconferenceLink.
   *
   * @param string $videoconferenceLink
   *
   * @return Meeting
   */
  public function setVideoconferenceLink($videoconferenceLink)
  {
    $this->videoconferenceLink = $videoconferenceLink;

    return $this;
  }

  /**
   * Get videoconferenceLink.
   *
   * @return string
   */
  public function getVideoconferenceLink()
  {
    return $this->videoconferenceLink;
  }

  /**
   * Set rescheduled.
   *
   * @param integer $rescheduled
   *
   * @return Meeting
   */
  public function setRescheduled($rescheduled)
  {
    $this->rescheduled = $rescheduled;

    return $this;
  }

  /**
   * Get rescheduled.
   *
   * @return integer
   */
  public function getRescheduled()
  {
    return $this->rescheduled;
  }

  /**
   * Set cancelLink.
   *
   * @param string $cancelLink
   *
   * @return Meeting
   */
  public function setCancelLink($cancelLink)
  {
    $this->cancelLink = $cancelLink;

    return $this;
  }

  /**
   * Get cancelLink.
   *
   * @return string|null
   */
  public function getCancelLink()
  {
    return $this->cancelLink;
  }


  /**
   * Set draftExpiration.
   *
   * @param \DateTime $draftExpiration
   *
   * @return Meeting
   */
  public function setDraftExpiration(?DateTime $draftExpiration): Meeting
  {
    $this->draftExpiration = $draftExpiration;

    return $this;
  }

  /**
   * Get draftExpiration.
   *
   * @return \DateTime
   */
  public function getDraftExpiration()
  {
    return $this->draftExpiration;
  }

  /**
   * @return mixed
   */
  public function getFirstAvailableDate()
  {
    return $this->firstAvailableDate;
  }

  /**
   * @param mixed $firstAvailableDate
   */
  public function setFirstAvailableDate($firstAvailableDate): void
  {
    $this->firstAvailableDate = $firstAvailableDate;
  }

  /**
   * @return mixed
   */
  public function getFirstAvailableStartTime()
  {
    return $this->firstAvailableStartTime;
  }

  /**
   * @param mixed $firstAvailableStartTime
   */
  public function setFirstAvailableStartTime($firstAvailableStartTime): Meeting
  {
    $this->firstAvailableStartTime = $firstAvailableStartTime;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getFirstAvailableEndTime()
  {
    return $this->firstAvailableEndTime;
  }

  /**
   * @param mixed $firstAvailableEndTime
   */
  public function setFirstAvailableEndTime($firstAvailableEndTime): Meeting
  {
    $this->firstAvailableEndTime = $firstAvailableEndTime;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getFirstAvailabilityUpdatedAt()
  {
    return $this->firstAvailabilityUpdatedAt;
  }

  /**
   * @param mixed $firstAvailabilityUpdatedAt
   */
  public function setFirstAvailabilityUpdatedAt($firstAvailabilityUpdatedAt): Meeting
  {
    $this->firstAvailabilityUpdatedAt = $firstAvailabilityUpdatedAt;
    return $this;
  }

  public static function getStatuses()
  {
    $statuses = [];
    $class = new \ReflectionClass(__CLASS__);
    $constants = $class->getConstants();
    foreach ($constants as $name => $value) {
      if (strpos($name, 'STATUS_') === 0) {
        $statuses[$value] = [
          'id' => $value,
          'identifier' => $name,
        ];
      }
    }
    return $statuses;
  }
}


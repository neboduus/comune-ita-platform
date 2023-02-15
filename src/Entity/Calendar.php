<?php

namespace App\Entity;

use App\Model\ExternalCalendar;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Nelmio\ApiDocBundle\Annotation\Model;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;
use App\Model\DateTimeInterval;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Calendar
 *
 * @ORM\Table(name="calendar")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Calendar
{
  // Default minimum and maximum time range for calendar view
  const MIN_DATE = '07:00';
  const MAX_DATE = '18:00';
  const SLOT_DURATION = 30;

  const DEFAULT_DRAFT_DURATION = 600;
  const DEFAULT_DRAFT_INCREMENT = 5*24*60*60;
  const DEFAULT_ROLLING_DAYS = 30;
  const DEFAULT_CANCEL_DAYS = 3;

  const TYPE_TIME_FIXED = "time_fixed_slots";
  const TYPE_TIME_VARIABLE = "time_variable_slots";

  const MINIMUM_SCHEDULING_NOTICES_OPTIONS = [
    'login_type.none' => 0,
    'time.one_hour_before' => 1,
    'time.two_hours_before' => 2,
    'time.four_hours_before' => 4,
    'time.eight_hours_before' => 8,
    'time.one_day_before' => 24,
    'time.two_days_before' => 48,
    'time.three_days_before' => 72,
    'time.one_week_before' => 168,
    'time.two_weeks_before' => 336,
    'time.three_weeks_before' => 504,
    'time.one_month_before' => 720
  ];

  const CALENDAR_TYPES = [
    "calendars.type.time_fixed_slots" => self::TYPE_TIME_FIXED,
    "calendars.type.time_variable_slots" => self::TYPE_TIME_VARIABLE
  ];

  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   * @OA\Property(description="Calendar's uuid", type="string")
   * @Groups({"read", "kafka"})
   */
  private $id;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\User")
   * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", nullable=false)
   * @Assert\NotBlank(message="Questo campo è obbligatorio (owner)")
   * @OA\Property(description="Calendar's owner id", type="string")
   * @Serializer\Exclude()
   */
  private $owner;

  /**
   * @var string
   *
   * @ORM\Column(name="title", type="string", length=255, unique=true)
   * @Assert\NotBlank(message="Questo campo è obbligatorio (title)")
   * @OA\Property(description="Calendar's title", type="string")
   * @Groups({"read", "kafka"})
   */
  private $title;

  /**
   * @var string
   *
   * @ORM\Column(name="type", type="string", length=255, options={"default" : Calendar::TYPE_TIME_FIXED})
   * @Assert\Choice(choices=Calendar::CALENDAR_TYPES, message="Choose a valid type.")
   * @OA\Property(description="Calendar's slots type", type="string")
   * @Groups({"read", "kafka"})
   */
  private $type = self::TYPE_TIME_FIXED;

  /**
   * @var string|null
   *
   * @ORM\Column(name="contact_email", type="string", length=255, nullable=true)
   * @Assert\Email(message="Email non valida")
   * @OA\Property(description="Calendar's contact email", type="string")
   * @Groups({"read", "kafka"})
   */
  private $contactEmail;

  /**
   * @var int
   *
   * @ORM\Column(name="rolling_days", type="integer")
   * @Assert\LessThanOrEqual(message="Maximum window is 180 days", value=180)
   * @OA\Property(description="Calendar's rolling days", type="integer")
   * @Groups({"read", "kafka"})
   */
  private $rollingDays;

  /**
   * @var int
   *
   * @ORM\Column(name="drafts_duration", type="integer", nullable=false)
   * @OA\Property(description="Calendar draft meetings duration (minutes)", type="integer")
   * @Assert\GreaterThan(0, message="La durata delle bozza deve avere un valore positivo")
   * @Groups({"kafka"})
   */
  private $draftsDuration;


  /**
   * @var int
   *
   * @ORM\Column(name="drafts_duration_increment", type="integer", nullable=true)
   * @Assert\GreaterThanOrEqual (0, message="La durata dell'incremento della bozza deve avere un valore positivo")
   * @OA\Property(description="Calendar draft meetings duration increment (days)", type="integer")
   * @Groups({"kafka"})
   */
  private $draftsDurationIncrement;

  /**
   * @var int
   *
   * @ORM\Column(name="minimum_scheduling_notice", type="integer", nullable=true)
   * @OA\Property(description="Calendar's minimum scheduling notice", type="integer")
   * @Assert\Expression(
   *     "this.getRollingDays() * 24 > value",
   *     message="Must be less than rolling days"
   * )
   * @Groups({"read", "kafka"})
   */
  private $minimumSchedulingNotice;

  /**
   * @var int
   *
   * @ORM\Column(name="allow_cancel_days", type="integer", nullable=true)
   * @OA\Property(description="Calendar's minimum days to allow cancel", type="integer")
   * @Groups({"read", "kafka"})
   */
  private $allowCancelDays;


  /**
   * @var bool
   *
   * @ORM\Column(name="allow_overlaps", type="boolean", options={"default" : 0})
   * @OA\Property(description="Allow calendar's opening hours overlaps", type="boolean")
   * @Groups({"read", "kafka"})
   */
  private $allowOverlaps;

  /**
   * @var bool
   *
   * @ORM\Column(name="is_moderated", type="boolean")
   * @OA\Property(description="Calendar's moderation mode", type="boolean")
   * @Groups({"read", "kafka"})
   */
  private $isModerated;

  /**
   * Many Calendars have Many Operators.
   * @ORM\ManyToMany(targetEntity="App\Entity\OperatoreUser")
   * @ORM\JoinTable(name="calendars_operators",
   *      joinColumns={@ORM\JoinColumn(name="calendar_id", referencedColumnName="id")},
   *      inverseJoinColumns={@ORM\JoinColumn(name="operator_id", referencedColumnName="id")}
   *      )
   * @OA\Property(description="Calendar's moderators", type="array", @OA\Items(type="string"))
   * @Serializer\Exclude()
   */
  private $moderators;

  /**
   * @ORM\OneToMany(targetEntity="App\Entity\OpeningHour", mappedBy="calendar", cascade={"persist"})
   * @Serializer\Exclude()
   */
  private $openingHours;

  /**
   * @var string
   *
   * @ORM\Column(name="location", type="text")
   * @Assert\NotBlank(message="Questo campo è obbligatorio (location)")
   * @OA\Property(description="Calendar's location", type="string")
   * @Groups({"read", "kafka"})
   */
  private $location;

  /**
   * @ORM\Column(name="external_calendars", type="json", nullable=true)
   * @OA\Property(description="Calendar's external calendars", type="array", @OA\Items(ref=@Model(type=ExternalCalendar::class)))
   * @Groups({"read", "kafka"})
   */
  private $externalCalendars;

  /**
   * @var DateTimeInterval[]
   *
   * @ORM\Column(name="closing_periods", type="json", nullable=true)
   * @OA\Property(description="Calendar's closing periods", type="array", @OA\Items(ref=@Model(type=DateTimeInterval::class)))
   * @Groups({"read", "kafka"})
   */
  private $closingPeriods;

  /**
   * @ORM\Column(type="datetime")
   * @OA\Property(description="Calendar's creation date")
   * @Groups({"read", "kafka"})
   */
  private $createdAt;

  /**
   * @ORM\Column(type="datetime")
   * @OA\Property(description="Calendar's last modified date")
   * @Groups({"read", "kafka"})
   */
  private $updatedAt;

  /**
   * Calendar constructor.
   * @throws \Exception
   */
  public function __construct()
  {
    if (!$this->id) {
      $this->id = Uuid::uuid4();
      $this->type = self::TYPE_TIME_FIXED;
      $this->isModerated = false;
      $this->moderators = new ArrayCollection();
      $this->openingHours = new ArrayCollection();
      $this->closingPeriods = new ArrayCollection();
      $this->externalCalendars = new ArrayCollection();
      $this->allowCancelDays = self::DEFAULT_CANCEL_DAYS;
      $this->rollingDays = self::DEFAULT_ROLLING_DAYS;
      $this->draftsDuration = self::DEFAULT_DRAFT_DURATION;
      $this->draftsDurationIncrement = self::DEFAULT_DRAFT_INCREMENT;
      $this->allowOverlaps = false;
      $this->minimumSchedulingNotice = 0;
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
   * Set title.
   *
   * @param string $title
   *
   * @return Calendar
   */
  public function setTitle($title)
  {
    $this->title = $title;

    return $this;
  }

  /**
   * Get title.
   *
   * @return string
   */
  public function getTitle()
  {
    return $this->title;
  }

  /**
   * Set type.
   *
   * @param string $type
   *
   * @return Calendar
   */
  public function setType($type)
  {
    $this->type = $type;

    return $this;
  }

  /**
   * Get title.
   *
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }

  /**
   * Get Owner
   *
   * @return User|null
   */
  public function getOwner(): ?User
  {
    return $this->owner;
  }

  /**
   * Get Owner
   *
   * @param User|null $owner
   * @return $this
   */
  public function setOwner(?User $owner): self
  {
    $this->owner = $owner;

    return $this;
  }

  /**
   * @Serializer\VirtualProperty()
   * @Serializer\Type("string")
   * @Serializer\SerializedName("owner")
   * @Groups({"read"})
   */
  public function getOwnerId(): string
  {
    return $this->owner->getId();
  }

  /**
   * @Serializer\VirtualProperty()
   * @Serializer\Type("string")
   * @Serializer\SerializedName("owner_id")
   * @Groups({"kafka"})
   */
  public function getOwnerIdKafka(): string
  {
    return $this->owner->getId();
  }

  /**
   * Set contactEmail.
   *
   * @param string|null $contactEmail
   *
   * @return Calendar
   */
  public function setContactEmail($contactEmail = null)
  {
    $this->contactEmail = $contactEmail;

    return $this;
  }

  /**
   * Get contactEmail.
   *
   * @return string|null
   */
  public function getContactEmail()
  {
    return $this->contactEmail;
  }

  /**
   * Set rollingDays.
   *
   * @param int $rollingDays
   *
   * @return Calendar
   */
  public function setRollingDays($rollingDays)
  {
    $this->rollingDays = $rollingDays;

    return $this;
  }

  /**
   * Get rollingDays.
   *
   * @return int
   */
  public function getRollingDays()
  {
    return $this->rollingDays;
  }

  /**
   * Set minimumSchedulingNotice.
   *
   * @param int $minimumSchedulingNotice
   *
   * @return Calendar
   */
  public function setMinimumSchedulingNotice($minimumSchedulingNotice)
  {
    $this->minimumSchedulingNotice = $minimumSchedulingNotice;

    return $this;
  }

  /**
   * Get minimumSchedulingNotice.
   *
   * @return int
   */
  public function getMinimumSchedulingNotice()
  {
    return ($this->minimumSchedulingNotice ?? 0);
  }

  /**
   * Set drafts duration.
   *
   * @param int $draftsDuration
   *
   * @return Calendar
   */
  public function setDraftsDuration($draftsDuration)
  {
    $this->draftsDuration = $draftsDuration;

    return $this;
  }

  /**
   * Get drafts duration.
   *
   * @return int
   */
  public function getDraftsDuration()
  {
    return $this->draftsDuration;
  }

  /**
   * Set drafts duration increment.
   *
   * @param int $draftsDurationIncrement
   *
   * @return Calendar
   */
  public function setDraftsDurationIncrement($draftsDurationIncrement)
  {
    $this->draftsDurationIncrement = $draftsDurationIncrement;

    return $this;
  }

  /**
   * Get drafts duration increment.
   *
   * @return int
   */
  public function getDraftsDurationIncrement()
  {
    return $this->draftsDurationIncrement;
  }

  /**
   * Set allowCancelDays.
   *
   * @param int $allowCancelDays
   *
   * @return Calendar
   */
  public function setAllowCancelDays($allowCancelDays)
  {
    $this->allowCancelDays = $allowCancelDays;

    return $this;
  }

  /**
   * Get allowCancelDays.
   *
   * @return int
   */
  public function getAllowCancelDays()
  {
    return $this->allowCancelDays;
  }

  /**
   * Set isModerated.
   *
   * @param bool $isModerated
   *
   * @return Calendar
   */
  public function setIsModerated($isModerated)
  {
    $this->isModerated = $isModerated;

    return $this;
  }

  /**
   * Get isModerated.
   *
   * @return bool
   */
  public function getIsModerated()
  {
    return $this->isModerated;
  }

  /**
   * Set allowOverlaps.
   *
   * @param bool $allowOperlaps
   *
   * @return Calendar
   */
  public function setAllowOverlaps($allowOperlaps)
  {
    $this->allowOverlaps = $allowOperlaps;
    return $this;
  }

  /**
   * Get allowOperlaps.
   *
   * @return bool
   */
  public function isAllowOverlaps()
  {
    return $this->allowOverlaps ?? false;
  }

  /**
   * @return Collection
   */
  public function getModerators()
  {
    return $this->moderators;
  }

  /**
   * @param OperatoreUser[] $moderators
   *
   * @return $this
   */
  public function setModerators($moderators)
  {
    $this->moderators = $moderators;

    return $this;
  }

  /**
   * @Serializer\VirtualProperty(name="moderators")
   * @Serializer\Type("array")
   * @OA\Items(type="string")
   * @Serializer\SerializedName("moderators")
   * @Groups({"read"})
   */
  public function getModeratorsId(): array
  {
    $moderators = [];
    foreach ($this->getModerators() as $moderator) {
      $moderators[] = $moderator->getId();
    }
    return $moderators;
  }

  /**
   * @Serializer\VirtualProperty(name="moderator_ids")
   * @Serializer\Type("array")
   * @OA\Items(type="string")
   * @Serializer\SerializedName("moderator_ids")
   * @Groups({"kafka"})
   */
  public function getModeratorsIdKafka(): array
  {
    return $this->getModeratorsId();
  }

  /**
   * Get Calendar Opening Hours
   *
   * @return Collection|OpeningHour[]
   */
  public function getOpeningHours(): Collection
  {
    return $this->openingHours;
  }

  /**
   * Adds an opening hour
   *
   * @param OpeningHour $openingHour
   * @return $this
   */
  public function addOpeningHour(OpeningHour $openingHour): self
  {
    if (!$this->openingHours->contains($openingHour)) {
      $this->openingHours[] = $openingHour;
      $openingHour->setCalendar($this);
    }

    return $this;
  }

  /**
   * Removes an Opening Hour
   *
   * @param OpeningHour $openingHour
   * @return $this
   */
  public function removeOpeningHour(OpeningHour $openingHour): self
  {
    if ($this->openingHours->contains($openingHour)) {
      $this->openingHours->removeElement($openingHour);
      // set the owning side to null (unless already changed)
      if ($openingHour->getCalendar() === $this) {
        $openingHour->setCalendar(null);
      }
    }

    return $this;
  }

  /**
   * @Serializer\VirtualProperty(name="opening_hours")
   * @Serializer\Type("array<array>")
   * @Serializer\SerializedName("opening_hours")
   * @Groups({"read", "kafka"})
   */
  public function getOpeningHoursList(): array
  {
    $openingHours = [];
    foreach ($this->openingHours as $openingHour) {
      if ($openingHour->getEndDate() > new DateTime()) {
        $openingHours[] = ["id" => $openingHour->getId(),
          "name" => $openingHour->getName()
        ];
      }
    }
    return $openingHours;
  }

  /**
   * Set closingPeriods.
   *
   * @param array $closingPeriods
   *
   * @return Calendar
   */
  public function setClosingPeriods($closingPeriods)
  {
    $this->closingPeriods = $closingPeriods;

    return $this;
  }

  /**
   * Get closingPeriods.
   *
   * return DateTimeInterval[]
   */
  public function getClosingPeriods()
  {
    $closingPeriods = [];
    foreach ($this->closingPeriods as $closingPeriod) {
      $tmp = new DateTimeInterval();
      $tmp->setFromTime(new \DateTime($closingPeriod['from_time']));
      $tmp->setToTime(new \DateTime($closingPeriod['to_time']));
      $closingPeriods[] = $tmp;
    }
    return $closingPeriods;
  }

  /**
   * Set externalCalendars.
   *
   * @param string $externalCalendars
   *
   * @return Calendar
   */
  public function setExternalCalendars($externalCalendars)
  {
    $this->externalCalendars = $externalCalendars;

    return $this;
  }

  /**
   * Get externalCalendars.
   *
   * return ExternalCalendar[]
   */
  public function getExternalCalendars()
  {
    $externalCalendars = [];
    foreach ($this->externalCalendars as $externalCalendar) {
      $tmp = new ExternalCalendar();
      $tmp->setName($externalCalendar['name']);
      $tmp->setUrl($externalCalendar['url']);
      $externalCalendars[] = $tmp;
    }
    return $externalCalendars;
  }

  /**
   * Set location.
   *
   * @param string $location
   *
   * @return Calendar
   */
  public function setLocation($location)
  {
    $this->location = $location;

    return $this;
  }

  /**
   * Get location.
   *
   * @return string
   */
  public function getLocation()
  {
    return $this->location;
  }

  /**
   * Get createdAt.
   *
   * @return \DateTime
   */
  public function getCreatedAt(): ?\DateTimeInterface
  {
    return $this->createdAt;
  }

  /**
   * Set createdAt
   *
   * @param \DateTimeInterface $updated_at
   *
   * @return $this
   */
  public function setCreatedAt(\DateTimeInterface $created_at): self
  {
    $this->createdAt = $created_at;

    return $this;
  }

  /**
   * Get updatedAt
   *
   * @return \DateTimeInterface|null
   */
  public function getUpdatedAt(): ?\DateTimeInterface
  {
    return $this->updatedAt;
  }

  /**
   * Set updatedAt
   *
   * @param \DateTimeInterface $updated_at
   *
   * @return $this
   */
  public function setUpdatedAt(\DateTimeInterface $updated_at): self
  {
    $this->updatedAt = $updated_at;

    return $this;
  }


  /**
   * @Serializer\VirtualProperty(name="drafts_duration")
   * @Serializer\Type("int")
   * @Serializer\SerializedName("drafts_duration")
   * @Groups({"read", "kafka"})
   */
  public function getDraftDurationInMinutes()
  {
    return $this->getDraftsDuration() / (60);
  }

  /**
   * @Serializer\VirtualProperty(name="drafts_duration_increment")
   * @Serializer\Type("int")
   * @Serializer\SerializedName("drafts_duration_increment")
   * @Groups({"read", "kafka"})
   */
  public function getDraftDurationIncrementInDays()
  {
    return $this->getDraftsDurationIncrement() / (24*60*60);
  }

  /**
   * @ORM\PrePersist
   * @ORM\PreUpdate
   */
  public function updatedTimestampfs(): void
  {
    $dateTimeNow = new DateTime('now');

    $this->setUpdatedAt($dateTimeNow);

    if ($this->getCreatedAt() === null) {
      $this->setCreatedAt($dateTimeNow);
    }
  }

  /**
   * @return string
   */
  public function __toString()
  {
    return (string)$this->getTitle();
  }
}

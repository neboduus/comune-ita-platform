<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use OpenApi\Annotations as OA;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * OpeningHour
 *
 * @ORM\Entity
 * @ORM\Table(name="opening_hour")
 * @ORM\HasLifecycleCallbacks
 */
class OpeningHour
{
  const WEEKDAYS = [
    'calendars.opening_hours.weeks.week_day_1' => 1,
    'calendars.opening_hours.weeks.week_day_2' => 2,
    'calendars.opening_hours.weeks.week_day_3' => 3,
    'calendars.opening_hours.weeks.week_day_4' => 4,
    'calendars.opening_hours.weeks.week_day_5' => 5,
    'calendars.opening_hours.weeks.week_day_6' => 6,
    'calendars.opening_hours.weeks.week_day_7' => 7
  ];
  const WEEKDAYS_SHORT = [
    'Lu' => 1,
    'Ma' => 2,
    'Me' => 3,
    'Gio' => 4,
    'Ve' => 5,
    'Sa' => 6,
    'Do' => 7
  ];

  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   * @OA\Property(description="Opening Hour's uuid", type="string")
   * @Groups({"kafka"})
   */
  private $id;


  /**
   * @var string
   *
   * @ORM\Column(name="name", type="string", length=255, nullable=true)
   * @OA\Property(description="Opening hour's name", type="string")
   * @Groups({"kafka"})
   */
  private $name;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\Calendar", inversedBy="openingHours")
   * @ORM\JoinColumn(name="calendar_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
   * @Assert\NotBlank(message="Questo campo è obbligatorio (calendar)")
   * @OA\Property(description="Opening Hour's calendar id", type="string")
   * @Serializer\Exclude()
   */
  private $calendar;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="start_date", type="datetime")
   * @Assert\NotBlank(message="Questo campo è obbligatorio (startDate)")
   * @Assert\LessThanOrEqual(propertyPath="endDate", message="La data di inizio deve essere minore della data di fine")
   * @OA\Property(description="Opening Hour's start date")
   * @Groups({"kafka"})
   */
  private $startDate;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="end_date", type="datetime")
   * @Assert\NotBlank(message="Questo campo è obbligatorio (endDate)")
   * @Assert\GreaterThanOrEqual(propertyPath="startDate",  message="La data di fine deve essere maggiore della data di inizio")
   * @OA\Property(description="Opening Hour's end date")
   * @Groups({"kafka"})
   */
  private $endDate;

  /**
   * @var array
   *
   * @ORM\Column(name="days_of_week", type="array")
   * @OA\Property(description="Opening Hour's days of week: 1:Monday - 7:Sunday", type="array", type="array", @OA\Items(type="integer"))
   * @Groups({"kafka"})
   */
  private $daysOfWeek;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="begin_hour", type="time")
   * @Assert\NotBlank(message="Questo campo è obbligatorio (beginHour)")
   * @Assert\LessThan(propertyPath="endHour",  message="L'orario di inizio deve essere minore dell'orario di fine")
   * @OA\Property(description="Opening Hour's begin hour")
   * @Serializer\Type("DateTime<'H:i'>")
   * @Groups({"kafka"})
   */
  private $beginHour;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="end_hour", type="time")
   * @Assert\NotBlank(message="Questo campo è obbligatorio (endHour)")
   * @Assert\GreaterThan(propertyPath="beginHour",  message="L'orario di fine deve essere maggiore dell'orario di inizio")
   * @OA\Property(description="Opening Hour's end hour")
   * @Serializer\Type("DateTime<'H:i'>")
   * @Groups({"kafka"})
   */
  private $endHour;

  /**
   * @var bool
   *
   * @ORM\Column(name="is_moderated", type="boolean", options={"default" : 0})
   * @OA\Property(description="Calendar's moderation mode", type="boolean")
   * @Groups({"kafka"})
   */
  private $isModerated;

  /**
   * @var int
   *
   * @ORM\Column(name="meeting_minutes", type="integer",options={"default" : 30})
   * @Assert\NotBlank(message="Questo campo è obbligatorio (meetingMinutes)")
   * @OA\Property(description="Opening Hour's meeting minutes", type="integer")
   * @Groups({"kafka"})
   */
  private $meetingMinutes;

  /**
   * @var int
   *
   * @ORM\Column(name="interval_minutes", type="integer",options={"default" : 0}, nullable=true)
   * @OA\Property(description="Opening Hour's interval minutes between meetings", type="integer")
   * @Groups({"kafka"})
   */
  private $intervalMinutes;

  /**
   * @ORM\OneToMany(targetEntity="App\Entity\Meeting", mappedBy="openingHour")
   * @Serializer\Exclude()
   */
  private $meetings;

  /**
   * @var int
   *
   * @ORM\Column(name="meeting_queue", type="integer", options={"default" : 1})
   * @Assert\GreaterThanOrEqual(1)
   * @Assert\NotNull(message="Questo valore è obbligatorio (meetingQueue)")
   * @OA\Property(description="Opening Hour's meeting queue", type="integer")
   * @Groups({"kafka"})
   */
  private $meetingQueue = 1;

  /**
   * @ORM\Column(type="datetime")
   * @OA\Property(description="Calendar's creation date")
   * @Groups({"kafka"})
   */
  private $createdAt;

  /**
   * @ORM\Column(type="datetime")
   * @OA\Property(description="Calendar's last modified date")
   * @Groups({"kafka"})
   */
  private $updatedAt;

  /**
   * Opening Hour constructor.
   * @throws \Exception
   */
  public function __construct()
  {
    if (!$this->id) {
      $this->id = Uuid::uuid4();
    }
    $this->meetings = new ArrayCollection();
    $this->setMeetingQueue(1);
    $this->setMeetingMinutes(30);
    $this->setIntervalMinutes(0);
    $this->setIsModerated(false);
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
   * Set name.
   * @param string $name
   * @return OpeningHour
   */
  public function setName(string $name)
  {
    $this->name = $name;
    return $this;
  }

  /**
   * Get name.
   * @return string
   */
  public function getName()
  {
    return $this->name ?? $this->getDefaultName();
  }

  /**
   * Set startDate.
   *
   * @param \DateTime $startDate
   *
   * @return OpeningHour
   */
  public function setStartDate($startDate)
  {
    $this->startDate = $startDate;

    return $this;
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
   * Get Calendar
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
   * Get startDate.
   *
   * @return \DateTime
   */
  public function getStartDate()
  {
    return $this->startDate;
  }

  /**
   * Set endDate.
   *
   * @param \DateTime $endDate
   *
   * @return OpeningHour
   */
  public function setEndDate($endDate)
  {
    $this->endDate = $endDate;

    return $this;
  }

  /**
   * Get endDate.
   *
   * @return \DateTime
   */
  public function getEndDate()
  {
    return $this->endDate;
  }

  /**
   * Set daysOfWeek.
   *
   * @param array $daysOfWeek
   *
   * @return OpeningHour
   */
  public function setDaysOfWeek($daysOfWeek)
  {
    $this->daysOfWeek = $daysOfWeek;

    return $this;
  }

  /**
   * Get daysOfWeek.
   *
   * @return array
   */
  public function getDaysOfWeek()
  {
    return $this->daysOfWeek;
  }

  /**
   * Set beginHour.
   *
   * @param \DateTime $beginHour
   *
   * @return OpeningHour
   */
  public function setBeginHour($beginHour)
  {
    $this->beginHour = $beginHour;

    return $this;
  }

  /**
   * Get beginHour.
   *
   * @return \DateTime
   */
  public function getBeginHour()
  {
    return $this->beginHour;
  }

  /**
   * Set endHour.
   *
   * @param \DateTime $endHour
   *
   * @return OpeningHour
   */
  public function setEndHour($endHour)
  {
    $this->endHour = $endHour;

    return $this;
  }

  /**
   * Get endHour.
   *
   * @return \DateTime
   */
  public function getEndHour()
  {
    return $this->endHour;
  }

  /**
   * Set isModerated.
   *
   * @param bool $isModerated
   *
   * @return OpeningHour
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
   * Set meetingMinutes.
   *
   * @param int $meetingMinutes
   *
   * @return OpeningHour
   */
  public function setMeetingMinutes($meetingMinutes)
  {
    $this->meetingMinutes = $meetingMinutes;

    return $this;
  }

  /**
   * Get meetingMinutes.
   *
   * @return int
   */
  public function getMeetingMinutes()
  {
    return $this->meetingMinutes;
  }

  /**
   * Set intervalMinutes.
   *
   * @param int $intervalMinutes
   *
   * @return OpeningHour
   */
  public function setIntervalMinutes($intervalMinutes)
  {
    $this->intervalMinutes = $intervalMinutes;

    return $this;
  }

  /**
   * Get intervalMinutes.
   *
   * @return int
   */
  public function getIntervalMinutes()
  {
    return $this->intervalMinutes;
  }

  /**
   * Set meetingQueue.
   *
   * @param int $meetingQueue
   *
   * @return OpeningHour
   */
  public function setMeetingQueue($meetingQueue)
  {
    $this->meetingQueue = $meetingQueue;

    return $this;
  }

  /**
   * Get meetingQueue.
   *
   * @return int
   */
  public function getMeetingQueue()
  {
    return $this->meetingQueue;
  }

  /**
   * Get Opening Hour Meetings
   *
   * @return Collection|Meeting[]
   */
  public function getMeetings(): Collection
  {
    return $this->meetings;
  }

  /**
   * Adds a meeting
   *
   * @param Meeting $meeting
   * @return $this
   */
  public function addMeetings(Meeting $meeting): self
  {
    if (!$this->meetings->contains($meeting)) {
      $this->meetings[] = $meeting;
      $meeting->setOpeningHour($this);
    }

    return $this;
  }

  /**
   * Removes a Meeting
   *
   * @param Meeting $meeting
   * @return $this
   */
  public function removeMeetings(Meeting $meeting): self
  {
    if ($this->meetings->contains($meeting)) {
      $this->meetings->removeElement($meeting);
      // set the owning side to null (unless already changed)
      if ($meeting->getOpeningHour() === $this) {
        $meeting->setOpeningHour(null);
      }
    }

    return $this;
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
   * @ORM\PrePersist
   * @ORM\PreUpdate
   */
  public function updatedTimestamps(): void
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
    return (string)$this->getName();
  }


  /**
   * Get default name based on opening hour interval and days
   * @return string
   */
  public function getDefaultName(): string
  {
    $name = "";
    if ($this->daysOfWeek && $this->beginHour  && $this->endHour) {
      $name = implode(', ', $this->daysOfWeek ?? []);
      $name = str_replace($this->daysOfWeek, array_flip(self::WEEKDAYS_SHORT), $name);
      $name = $name . ' | ' . $this->getBeginHour()->format('H:i') . ' - ' . $this->getEndHour()->format('H:i');
    }
    return $name;
  }
}

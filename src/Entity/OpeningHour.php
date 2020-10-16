<?php

namespace App\Entity;

use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\ORMException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Swagger\Annotations as SWG;
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
  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   * @SWG\Property(description="Opening Hour's uuid", type="string")
   */
  private $id;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\Calendar", inversedBy="openingHours")
   * @ORM\JoinColumn(name="calendar_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
   * @Assert\NotBlank(message="Questo campo è obbligatorio (calendar)")
   * @SWG\Property(description="Opening Hour's calendar id", type="string")
   * @Serializer\Exclude()
   */
  private $calendar;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="start_date", type="datetime")
   * @Assert\NotBlank(message="Questo campo è obbligatorio (startDate)")
   * @SWG\Property(description="Opening Hour's start date")
   */
  private $startDate;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="end_date", type="datetime")
   * @Assert\NotBlank(message="Questo campo è obbligatorio (endDate)")
   * @SWG\Property(description="Opening Hour's end date")
   */
  private $endDate;

  /**
   * @var array
   *
   * @ORM\Column(name="days_of_week", type="array")
   * @SWG\Property(description="Opening Hour's days of week: 1:Monday - 7:Sunday", type="array", type="array", @SWG\Items(type="integer"))
   */
  private $daysOfWeek;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="begin_hour", type="time")
   * @Assert\NotBlank(message="Questo campo è obbligatorio (beginHour)")
   * @SWG\Property(description="Opening Hour's begin hour")
   * @Serializer\Type("DateTime<'H:i'>")
   */
  private $beginHour;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="end_hour", type="time")
   * @Assert\NotBlank(message="Questo campo è obbligatorio (endHour)")
   * @SWG\Property(description="Opening Hour's end hour")
   * @Serializer\Type("DateTime<'H:i'>")
   */
  private $endHour;

  /**
   * @var int
   *
   * @ORM\Column(name="meeting_minutes", type="integer",options={"default" : 30})
   * @Assert\NotBlank(message="Questo campo è obbligatorio (meetingMinutes)")
   * @SWG\Property(description="Opening Hour's meeting minutes", type="integer")
   */
  private $meetingMinutes;

  /**
   * @var int
   *
   * @ORM\Column(name="interval_minutes", type="integer",options={"default" : 0}, nullable=true)
   * @SWG\Property(description="Opening Hour's interval minutes between meetings", type="integer")
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
   * @SWG\Property(description="Opening Hour's meeting queue", type="integer")
   */
  private $meetingQueue = 1;

  /**
   * @ORM\Column(type="datetime")
   * @SWG\Property(description="Calendar's creation date")
   */
  private $createdAt;

  /**
   * @ORM\Column(type="datetime")
   * @SWG\Property(description="Calendar's last modified date")
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
    return (string)$this->getId();
  }
}

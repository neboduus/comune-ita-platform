<?php

namespace AppBundle\Entity;

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
   * @SWG\Property(description="Opening Hour's uuid", type="guid")
   */
  private $id;

  /**
   * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Calendar", inversedBy="openingHours")
   * @ORM\JoinColumn(name="calendar_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
   * @Assert\NotBlank(message="Questo campo è obbligatorio (calendar)")
   * @SWG\Property(description="Opening Hour's calendar id", type="guid")
   * @Serializer\Exclude()
   */
  private $calendar;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="start_date", type="datetime")
   * @Assert\NotBlank(message="Questo campo è obbligatorio (startDate)")
   * @SWG\Property(description="Opening Hour's start date", type="dateTime")
   */
  private $startDate;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="end_date", type="datetime")
   * @Assert\NotBlank(message="Questo campo è obbligatorio (endDate)")
   * @SWG\Property(description="Opening Hour's end date", type="dateTime")
   */
  private $endDate;

  /**
   * @var array
   *
   * @ORM\Column(name="days_of_week", type="array")
   * @SWG\Property(description="Opening Hour's days of week", type="array<int>")
   */
  private $daysOfWeek;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="begin_hour", type="time")
   * @Assert\NotBlank(message="Questo campo è obbligatorio (beginHour)")
   * @SWG\Property(description="Opening Hour's begin hour", type="time")
   * @Serializer\Type("DateTime<'H:i'>")
   */
  private $beginHour;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="end_hour", type="time")
   * @Assert\NotBlank(message="Questo campo è obbligatorio (endHour)")
   * @SWG\Property(description="Opening Hour's end hour", type="time")
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
   * @ORM\OneToMany(targetEntity="AppBundle\Entity\Meeting", mappedBy="openingHour")
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
   * @SWG\Property(description="Calendar's creation date", type="dateTime")
   */
  private $createdAt;

  /**
   * @ORM\Column(type="datetime")
   * @SWG\Property(description="Calendar's last modified date", type="dateTime")
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
   * @ORM\PrePersist
   * @ORM\PreUpdate
   * @param LifecycleEventArgs $args
   * @throws ORMException
   */
  public function checkOverlaps(LifecycleEventArgs $args): void
  {
    $em = $args->getEntityManager();
    $openingHours = $em->createQueryBuilder()
      ->select('openingHour')
      ->from('AppBundle:OpeningHour', 'openingHour')
      ->where('openingHour.calendar = :calendar')
      ->andWhere('openingHour.id != :id')
      ->andWhere('openingHour.startDate <= :endDate')
      ->andWhere('openingHour.endDate >= :startDate')
      ->setParameter('id', $this->id)
      ->setParameter('calendar', $this->calendar)
      ->setParameter('startDate', $this->startDate)
      ->setParameter('endDate', $this->endDate)
      ->getQuery()->getResult();

    if (!empty($openingHours)) {
      foreach ($openingHours as $openingHour) {
        if ($openingHour->beginHour <= $this->endHour && $this->beginHour <= $openingHour->endHour) {
          throw new ORMException("Different opening hours of the same calendar can't overlap");
        }
      }
    }
  }

  /**
   * @return string
   */
  public function __toString()
  {
    return (string)$this->getId();
  }

  /**
   * Returns array of opening hour slots by date
   *
   * @param DateTime $date
   * @return array
   * @throws \Exception
   */
  public function explodeMeetings(DateTime $date)
  {
    $closures = $this->getCalendar()->getClosingPeriods();
    $intervals = [];
    if ($this->startDate > $date || $this->endDate < $date)
      return $intervals;
    $meetingInterval = new DateInterval('PT' . ($this->meetingMinutes + $this->intervalMinutes) . 'M');
    $dateString = $date->format('Y-m-d');
    $begin = (new DateTime($dateString))->setTime($this->beginHour->format('H'), $this->beginHour->format('i'));
    $end = (new DateTime($dateString))->setTime($this->endHour->format('H'), $this->endHour->format('i'));

    $periods = new DatePeriod($begin, $meetingInterval, $end);
    foreach ($periods as $period) {
      $shoudAdd = true;
      // Check if period falls on closure
      foreach ($closures as $closure) {
        if ($period >= $closure->getFromTime() && $period < $closure->getToTime())
          $shoudAdd = false;
      }

      $_begin = $period;
      $_end = clone $_begin;
      $_end = $_end->add($meetingInterval);
      if ($_end <= $end && $shoudAdd) {
        $intervals[$_begin->format('H:i') . '-' . $_end->modify('- ' . $this->getIntervalMinutes() . ' minutes')->format('H:i') . '-' . $this->getMeetingQueue()] = [
          'date' => $date->format('Y-m-d'),
          'start_time' => $_begin->format('H:i'),
          'end_time' => $_end->format('H:i'),
        ];
      }
    }
    return $intervals;
  }

  /**
   * Return array of available dates
   *
   * @return array
   * @throws \Exception
   */

  function explodeDays($all = false, $from = NULL, $to = NULL)
  {
    $closures = $this->getCalendar()->getClosingPeriods();
    $array = array();

    if ($all) {
      $start = max((new DateTime('now', new DateTimeZone('Europe/Rome'))), $this->startDate);
      $end = $this->endDate;
    } else if ($from) {
      $start = new DateTime($from);
      $end = new DateTime($to);
    } else {
      $noticeInterval = new DateInterval('PT' . $this->getCalendar()->getMinimumSchedulingNotice() . 'H');
      $start = max((new DateTime('now', new DateTimeZone('Europe/Rome')))->add($noticeInterval), $this->startDate);
      $rollingInterval = new DateInterval('P' . $this->getCalendar()->getRollingDays() . 'D');
      $end = min((new DateTime())->add($rollingInterval), $this->endDate);
    }
    // Variable that store the date interval of period 1 day
    $interval = new DateInterval('P1D');

    $this->endDate->add($interval);
    $period = new DatePeriod($start, $interval, $end);

    // Use loop to store date into array
    foreach ($period as $date) {
      $date = $date->setTimeZone(new DateTimeZone('Europe/Rome'));
      $shouldAdd = false;
      if (!$closures) $shouldAdd = true;
      foreach ($closures as $closure) {
        $closureStartDay = $closure->getFromTime()->format('Y-m-d');
        $closureEndDay = $closure->getToTime()->format('Y-m-d');
        $day = $date->format('Y-m-d');
        if ($day < $closureStartDay || $day > $closureEndDay) {
          // External
          $shouldAdd = true;
        } else if ($day == $closureStartDay) {
          /* Closure start date equals current date
           Check if opening begin hour is before closure hour */
          $dayOpening = DateTime::createFromFormat('Y-m-d:H:i', $day . ':' . $this->getBeginHour()->format('H:i'));
          if ($dayOpening < $closure->getFromTime()) {
            $shouldAdd = true;
          }
        } else if ($day == $closureEndDay) {
          /* Closure end date equals current date
          Check if opening begin hour is after closure hour*/
          $dayClosure = DateTime::createFromFormat('Y-m-d:H:i', $day . ':' . $this->getEndHour()->format('H:i'));
          if ($closure->getToTime() < $dayClosure) {
            $shouldAdd = true;
          }
        }
      }
      if ($shouldAdd && in_array($date->format('N'), $this->daysOfWeek)) {
        $array[] = $date->format('Y-m-d');
      }
    }
    return $array;
  }


  public function getInterval()
  {
    $slots = [];
    foreach ($this->explodeDays(true) as $date) {
      foreach ($this->explodeMeetings(new DateTime($date)) as $slot) {
        $now = (new DateTime('now', new DateTimeZone('Europe/Rome')))->format('Y-m-d:H:i');
        $startTime = (\DateTime::createFromFormat('Y-m-d:H:i', $slot['date'] . ':' . $slot['start_time']))->format('Y-m-d:H:i');

        if ($startTime > $now) {
          $start = DateTime::createFromFormat('Y-m-d:H:i', $slot['date'] . ':' . $slot['start_time'])->format('c');
          $end = DateTime::createFromFormat('Y-m-d:H:i', $slot['date'] . ':' . $slot['end_time'])->format('c');
          $slots[] = [
            'title' => 'Apertura',
            'start' => $start,
            'end' => $end,
            'rendering' => 'background',
            'color' => 'var(--blue)'
          ];
        }
      }
    }
    return $slots;
  }
}

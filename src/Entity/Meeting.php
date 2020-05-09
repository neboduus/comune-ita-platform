<?php

namespace App\Entity;

use DateTime;
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
 * Meeting
 *
 * @ORM\Table(name="meeting")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Meeting
{
    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REFUSED = 2;
    const STATUS_MISSED = 3;
    const STATUS_DONE = 4;
    const STATUS_CANCELLED = 5;

    /**
     * @ORM\Column(type="guid")
     * @ORM\Id
     * @SWG\Property(description="Meeting's uuid", type="string")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Calendar")
     * @ORM\JoinColumn(name="calendar_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @Assert\NotBlank(message="Questo campo è obbligatorio (calendar)")
     * @SWG\Property(description="Meeting's calendar id", type="string")
     * @Serializer\Exclude()
     * @var Calendar
     */
    private $calendar;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\OpeningHour", inversedBy="meetings")
     * @ORM\JoinColumn(name="opening_hour_id", referencedColumnName="id", nullable=true)
     * @SWG\Property(description="Meeting's opening hour id", type="string")
     * @Serializer\Exclude()
     */
    private $openingHour;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     * @SWG\Property(description="Meeting's user email", type="string")
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="phone_number", type="string", length=10, nullable=true)
     * @SWG\Property(description="Meeting's user phone number", type="string")
     */
    private $phoneNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="fiscal_code", type="string", length=16, nullable=true)
     * @SWG\Property(description="Meeting's user fiscal code", type="string")
     */
    private $fiscalCode;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     * @SWG\Property(description="Meeting's user name", type="string", )
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CPSUser")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     * @SWG\Property(description="Meeting's user id", type="string")
     * @Serializer\Exclude()
     * @var CPSUser
     */
    private $user;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="from_time", type="datetime")
     * @Assert\NotBlank(message="Questo campo è obbligatorio (from Time)")
     * @SWG\Property(description="Meeting's from Time")
     */
    private $fromTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="to_time", type="datetime")
     * @Assert\NotBlank(message="Questo campo è obbligatorio (to Time)")
     * @SWG\Property(description="Meeting's to Time")
     */
    private $toTime;

    /**
     * @var string
     *
     * @ORM\Column(name="user_message", type="text")
     * @Assert\NotBlank(message="Questo campo è obbligatorio (userMessage)")
     * @SWG\Property(description="Meeting's User Message", type="string")
     */
    private $userMessage;

    /**
     * @var string
     *
     * @ORM\Column(name="videoconference_link", type="string", nullable=true)
     * @Assert\Url(message="url non valido (videoconferenceLink)")
     * @SWG\Property(description="Meeting's videoconference link", type="string")
     */
    private $videoconferenceLink;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank(message="Seleziona un'opzione. Lo stato è un parametro obbligatorio")
     * @Assert\NotNull()
     * @SWG\Property(description="Meeting's status", type="integer")
     */
    private $status;

    /**
     * @ORM\Column(type="integer", nullable=false)
     * @SWG\Property(description="Meeting's rescheduled times", type="integer")
     */
    private $rescheduled;

    /**
     * @var string
     *
     * @ORM\Column(name="cancel_link", type="string", length=255, nullable=true)
     * @SWG\Property(description="Meeting's cancel link", type="string")
     * @Serializer\Exclude()
     */
    private $cancelLink;

    /**
     * @ORM\Column(type="datetime")
     * @SWG\Property(description="Meeting's creation date")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     * @SWG\Property(description="Meeting's last modified date")
     */
    private $updatedAt;

    /**
     * Meeting constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        if (!$this->id) {
            $this->id = Uuid::uuid4();
            $this->cancelLink = hash('sha256', $this->id . (new DateTime())->format('c'));
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
        if ($this->calendar->getIsModerated()) {
            $this->setStatus(self::STATUS_PENDING);
        } else {
            $this->setStatus(self::STATUS_APPROVED);
        }

        return $this;
    }

    /**
     * @Serializer\VirtualProperty(name="calendar")
     * @Serializer\Type("string")
     * @Serializer\SerializedName("calendar")
     */
    public function getCalendarId(): string
    {
        return $this->calendar->getId();
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
        if ($user) {
            $this->user = $user;
        }

        return $this;
    }

    /**
     * @Serializer\VirtualProperty(name="user")
     * @Serializer\Type("string")
     * @Serializer\SerializedName("user")
     * @Serializer\Exclude(if="!object.getUser()")
     */
    public function getUserId(): ?string
    {
        if ($this->user) {
            return $this->user->getId();
        } else {
            return null;
        }
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
     * Set videoconferenceLink.
     *
     * @param string $videoconferenceLink
     *
     * @return Meeting
     */
    public function setvideoconferenceLink($videoconferenceLink)
    {
        $this->videoconferenceLink = $videoconferenceLink;

        return $this;
    }

    /**
     * Get videoconferenceLink.
     *
     * @return string
     */
    public function getvideoconferenceLink()
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
     * @param \DateTimeInterface $created_at
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
     * Set createdAt and UpdatedAt
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     * @param LifecycleEventArgs $args
     * @throws \Exception
     */
    public function initMeeting(LifecycleEventArgs $args): void
    {
        $dateTimeNow = new DateTime('now');

        $this->setUpdatedAt($dateTimeNow);

        if ($this->getCreatedAt() === null) {
            $this->setRescheduled(0);
            $this->setCreatedAt($dateTimeNow);
        }
    }

    /**
     * Increment rescheduled
     *
     * @ORM\PreUpdate
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField('fromTime') && $event->hasChangedField('toTime')) {
            $this->setRescheduled($this->rescheduled + 1);
        }
    }
}

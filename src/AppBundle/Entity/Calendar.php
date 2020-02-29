<?php

namespace AppBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation\Model;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use JMS\Serializer\Annotation as Serializer;
use Swagger\Annotations as SWG;
use AppBundle\Model\DateTimeInterval;
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
  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   * @SWG\Property(description="Calendar's uuid", type="guid")
   */
  private $id;

  /**
   * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
   * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", nullable=false)
   * @Assert\NotBlank(message="Questo campo è obbligatorio (owner)")
   * @SWG\Property(description="Calendar's owner id", type="guid")
   * @Serializer\Exclude()
   */
  private $owner;

  /**
   * @var string
   *
   * @ORM\Column(name="title", type="string", length=255, unique=true)
   * @Assert\NotBlank(message="Questo campo è obbligatorio (title)")
   * @SWG\Property(description="Calendar's title", type="string")
   */
  private $title;

  /**
   * @var string|null
   *
   * @ORM\Column(name="contact_email", type="string", length=255, nullable=true)
   * @Assert\Email(message="Email non valida")
   * @SWG\Property(description="Calendar's contact email", type="email")
   */
  private $contactEmail;

  /**
   * @var int
   *
   * @ORM\Column(name="rolling_days", type="integer")
   * @Assert\LessThanOrEqual(
   *     message="Maximum window is 120 gg",
   *     value=120)
   * @SWG\Property(description="Calendar's rolling days", type="integer")
   */
  private $rollingDays;

  /**
   * @var bool
   *
   * @ORM\Column(name="is_moderated", type="boolean")
   * @Assert\NotBlank(message="questo campo è obbligatorio (isModerated)")
   * @SWG\Property(description="Calendar's moderation mode", type="boolean")
   */
  private $isModerated;

  /**
   * Many Calendars have Many Operators.
   * @ORM\ManyToMany(targetEntity="AppBundle\Entity\OperatoreUser")
   * @ORM\JoinTable(name="calendars_operators",
   *      joinColumns={@ORM\JoinColumn(name="calendar_id", referencedColumnName="id")},
   *      inverseJoinColumns={@ORM\JoinColumn(name="operator_id", referencedColumnName="id")}
   *      )
   * @SWG\Property(description="Calendar's moderators", type="array<guid>")
   * @Serializer\Exclude()
   */
  private $moderators;

  /**
   * @ORM\OneToMany(targetEntity="AppBundle\Entity\OpeningHour", mappedBy="calendar")
   */
  private $openingHours;

  /**
   * @var string
   *
   * @ORM\Column(name="location", type="text")
   * @Assert\NotBlank(message="Questo campo è obbligatorio (location)")
   * @SWG\Property(description="Calendar's location", type="string")
   */
  private $location;

  /**
   * @var DateTimeInterval[]
   *
   * @ORM\Column(name="closing_periods", type="json_array", nullable=true)
   * @SWG\Property(description="Calendar's closing periods", type="array", @SWG\Items(ref=@Model(type=DateTimeInterval::class)))
   */
  private $closingPeriods;

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
   * Calendar constructor.
   * @throws \Exception
   */
  public function __construct()
  {
    if (!$this->id) {
      $this->id = Uuid::uuid4();
      $this->moderators = new ArrayCollection();
      $this->openingHours = new ArrayCollection();
      $this->closingPeriods = new ArrayCollection();
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
   * @Serializer\VirtualProperty(name="owner")
   * @Serializer\Type("string")
   * @Serializer\SerializedName("owner")
   *
   */
  public function getOwnerId(): string
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
   * @Serializer\Type("array<string>")
   * @Serializer\SerializedName("moderators")
   *
   */
  public function getModeratorsId(): array
  {
    $moderators = [];
    foreach ($this->getModerators() as $moderator)
    {
      $moderators[] = $moderator->getId();
    }
    return $moderators;
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
  public function addOpeningHours(OpeningHour $openingHour): self
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
  public function removeOpeningHours(OpeningHour $openingHour): self
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
   * Set closingPeriods.
   *
   * @param string $closingPeriods
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
    return (string)$this->getTitle();
  }
}

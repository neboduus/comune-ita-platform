<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use JMS\Serializer\Annotation as Serializer;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Entity
 * @ORM\Table(name="subscriber")
 */
class Subscriber
{
    /**
     * @ORM\Column(type="guid")
     * @ORM\Id
     * @SWG\Property(description="Subscriber's uuid")
     * @Groups({"read"})
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="name")
     * @Assert\NotNull()
     * @SWG\Property(description="Subscriber's name")
     * @Groups({"read"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="surname")
     * @Assert\NotNull()
     * @SWG\Property(description="Subscriber's surname")
     * @Groups({"read"})
     */
    private $surname;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Subscription", mappedBy="subscriber")
     * @Serializer\Exclude()
     * @SWG\Property(description="Subscriber's subscriptions")
     */
    private $subscriptions;

    /**
     * @ORM\Column(type="date")
     * @Assert\NotBlank(message="date_of_birth")
     * @Assert\NotNull()
     * @SWG\Property(description="Subscriber's date of birth")
     * @Groups({"read"})
     */
    private $date_of_birth;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="place_of_birth")
     * @Assert\NotNull()
     * @SWG\Property(description="Subscriber's place of birth")
     * @Groups({"read"})
     */
    private $place_of_birth;

    /**
     * @ORM\Column(type="string", length=16)
     * @Assert\NotBlank(message="fiscal_code")
     * @Assert\NotNull()
     * @SWG\Property(description="Subscriber's fiscal code")
     * @Groups({"read"})
     */
    private $fiscal_code;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @SWG\Property(description="Subscriber's address")
     * @Groups({"read", "write"})
     */
    private $address;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @SWG\Property(description="Subscriber's house number")
     * @Groups({"read", "write"})
     */
    private $house_number;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @SWG\Property(description="Subscriber's municipality")
     * @Groups({"read", "write"})
     */
    private $municipality;

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     * @SWG\Property(description="Subscriber's postal code")
     * @Groups({"read", "write"})
     */
    private $postal_code;

    /**
     * @ORM\Column(type="string", length=255)
     * @SWG\Property(description="Subscriber's email")
     * @Groups({"read"})
     */
    private $email;


    public function __construct()
    {
        $this->id = Uuid::uuid4();
        $this->subscriptions = new ArrayCollection();
    }

  /**
   * @return UuidInterface
   */
  public function getId()
  {
    return $this->id;
  }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(string $surname): self
    {
        $this->surname = $surname;

        return $this;
    }

    /**
     * @return Collection|Subscription[]
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(Subscription $subscription): self
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions[] = $subscription;
            $subscription->setSubscriber($this);
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): self
    {
        if ($this->subscriptions->contains($subscription)) {
            $this->subscriptions->removeElement($subscription);
            // set the owning side to null (unless already changed)
            if ($subscription->getSubscriber() === $this) {
                $subscription->setSubscriber(null);
            }
        }

        return $this;
    }

    public function getDateOfBirth(): ?\DateTime
    {
        return $this->date_of_birth;
    }

    public function setDateOfBirth(\DateTime $date_of_birth): self
    {
        $this->date_of_birth = $date_of_birth;

        return $this;
    }

    public function getPlaceOfBirth(): ?string
    {
        return $this->place_of_birth;
    }

    public function setPlaceOfBirth(string $place_of_birth): self
    {
        $this->place_of_birth = $place_of_birth;

        return $this;
    }

    public function getFiscalCode(): ?string
    {
        return $this->fiscal_code;
    }

    public function setFiscalCode(string $fiscal_code): self
    {
        $this->fiscal_code = $fiscal_code;

        return $this;
    }



    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getHouseNumber(): ?string
    {
        return $this->house_number;
    }

    public function setHouseNumber(?string $house_number): self
    {
        $this->house_number = $house_number;

        return $this;
    }

    public function getMunicipality(): ?string
    {
        return $this->municipality;
    }

    public function setMunicipality(?string $municipality): self
    {
        $this->municipality = $municipality;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postal_code;
    }

    public function setPostalCode(?string $postal_code): self
    {
        $this->postal_code = $postal_code;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getCompleteName(): ?string
    {
      return $this->name . ' ' . $this->surname;
    }

  /**
   * @Serializer\VirtualProperty(name="subscriptions")
   * @Serializer\Type("array<string>")
   * @Serializer\SerializedName("subscriptions")
   * @Groups({"read"})
   */
  public function getSubscriptionsId(): array
  {
    $subscriptions = [];
    foreach ($this->getSubscriptions() as $subscription)
    {
      $subscriptions[] = $subscription->getId();
    }
    return $subscriptions;
  }

  public function isAdult(): bool
  {
    if ((new \DateTime())->diff($this->getDateOfBirth())->y < 18) {
      return false;
    }
    return true;
  }
}

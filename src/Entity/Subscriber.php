<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use JMS\Serializer\Annotation as Serializer;
use Swagger\Annotations as SWG;


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
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @SWG\Property(description="Subscriber's name")
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @SWG\Property(description="Subscriber's surname")
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
     * @SWG\Property(description="Subscriber's date of birth")
     */
    private $date_of_birth;

    /**
     * @ORM\Column(type="string", length=255)
     * @SWG\Property(description="Subscriber's place of birth")
     */
    private $place_of_birth;

    /**
     * @ORM\Column(type="string", length=16)
     * @SWG\Property(description="Subscriber's fiscal code")
     */
    private $fiscal_code;

    /**
     * @ORM\Column(type="string", length=255)
     * @SWG\Property(description="Subscriber's address")
     */
    private $address;

    /**
     * @ORM\Column(type="string", length=255)
     * @SWG\Property(description="Subscriber's house number")
     */
    private $house_number;

    /**
     * @ORM\Column(type="string", length=255)
     * @SWG\Property(description="Subscriber's municipality")
     */
    private $municipality;

    /**
     * @ORM\Column(type="string", length=5)
     * @SWG\Property(description="Subscriber's postal code")
     */
    private $postal_code;

    /**
     * @ORM\Column(type="string", length=255)
     * @SWG\Property(description="Subscriber's email")
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

    public function getDateOfBirth(): ?\DateTimeInterface
    {
        return $this->date_of_birth;
    }

    public function setDateOfBirth(\DateTimeInterface $date_of_birth): self
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

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getHouseNumber(): ?string
    {
        return $this->house_number;
    }

    public function setHouseNumber(string $house_number): self
    {
        $this->house_number = $house_number;

        return $this;
    }

    public function getMunicipality(): ?string
    {
        return $this->municipality;
    }

    public function setMunicipality(string $municipality): self
    {
        $this->municipality = $municipality;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postal_code;
    }

    public function setPostalCode(string $postal_code): self
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
}

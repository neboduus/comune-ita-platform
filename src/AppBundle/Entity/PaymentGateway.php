<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;


/**
 * @ORM\Entity
 * @ORM\Table(name="payment_gateway")
 * @ORM\HasLifecycleCallbacks
 */
class PaymentGateway
{
    const GATEWAY_DISABLED = 0;
    const GATEWAY_ENABLED = 1;

    /**
     * @ORM\Column(type="guid")
     * @ORM\Id
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100, unique=true)
     */
    private $identifier;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $disclaimer;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $fcqn;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $enabled;


    public function __construct()
    {
        if (!$this->id) {
            $this->id = Uuid::uuid4();
        }
        $this->enabled = self::GATEWAY_ENABLED;
    }


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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDisclaimer(): string
    {
        return $this->disclaimer;
    }

    /**
     * @param string $disclaimer
     */
    public function setDisclaimer(string $disclaimer)
    {
        $this->disclaimer = $disclaimer;
        return $this;
    }

    /**
     * @return string
     */
    public function getFcqn(): string
    {
        return $this->fcqn;
    }

    /**
     * @param string $fcqn
     */
    public function setFcqn(string $fcqn)
    {
        $this->fcqn = $fcqn;
        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function __toString() {
      return $this->getName();
    }
}

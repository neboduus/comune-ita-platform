<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
        return str_replace('AppBundle', 'App', $this->fcqn);
    }

    /**
     * @param string $fcqn
     * @return $this
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
     * @return $this
     */
    public function setEnabled(bool $enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }
}

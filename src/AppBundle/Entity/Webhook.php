<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Entity
 */
class Webhook
{
  const TRIGGERS = [
    'all' => 'Tutti',
    Pratica::STATUS_PAYMENT_SUCCESS => 'Pratica pagata',
    Pratica::STATUS_PRE_SUBMIT => 'Pratica inviata',
    Pratica::STATUS_SUBMITTED => 'Pratica acquisita',
    Pratica::STATUS_REGISTERED => 'Pratica protocollata',
    Pratica::STATUS_PENDING => 'Pratica presa in carico',
    Pratica::STATUS_COMPLETE => 'Pratica accettata',
    Pratica::STATUS_CANCELLED => 'Pratica rifiutata'
  ];

  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   */
  private $id;

  /**
   * @ORM\ManyToOne(targetEntity="Ente", inversedBy="webhooks")
   * @ORM\JoinColumn(name="ente_id", referencedColumnName="id")
   * @Serializer\Exclude()
   */
  private $ente;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   * @Assert\NotBlank(message="Title is mandatory")
   * @Assert\NotNull(message="Title is mandatory")
   */
  private $title;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   * @Assert\NotBlank(message="Title is mandatory")
   * @Assert\NotNull(message="Title is mandatory")
   * @Assert\Url(message="Endpoint msut be a valid url")
   */
  private $endpoint;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   *
   */
  private $method = 'POST';

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   *
   */
  private $trigger;

  /**
   * @ORM\Column(type="json", nullable=true)
   */
  private $filters;

  /**
   * @var string
   * @ORM\Column(type="text", nullable=true)
   */
  private $headers;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=true)
   */
  private $active;

  public function __construct()
  {
    if (!$this->id) {
      $this->id = Uuid::uuid4();
    }
  }

  /**
   * @return UuidInterface
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * @return mixed
   */
  public function getEnte()
  {
    return $this->ente;
  }

  /**
   * @param mixed $ente
   */
  public function setEnte($ente): void
  {
    $this->ente = $ente;
  }

  /**
   * @return string
   */
  public function getTitle(): ?string
  {
    return $this->title;
  }

  /**
   * @param string $title
   */
  public function setTitle(string $title): void
  {
    $this->title = $title;
  }

  /**
   * @return string
   */
  public function getEndpoint(): ?string
  {
    return $this->endpoint;
  }

  /**
   * @param string $endpoint
   */
  public function setEndpoint(string $endpoint): void
  {
    $this->endpoint = $endpoint;
  }

  /**
   * @return string
   */
  public function getMethod(): ?string
  {
    return $this->method;
  }

  /**
   * @param string $method
   */
  public function setMethod(string $method): void
  {
    $this->method = $method;
  }

  /**
   * @return string
   */
  public function getTrigger(): ?string
  {
    return $this->trigger;
  }

  /**
   * @param string $trigger
   */
  public function setTrigger(string $trigger): void
  {
    $this->trigger = $trigger;
  }

  /**
   * @return mixed
   */
  public function getFilters()
  {
    return $this->filters;
  }

  /**
   * @param mixed $filters
   */
  public function setFilters($filters): void
  {
    $this->filters = $filters;
  }

  /**
   * @return string
   */
  public function getHeaders(): ?string
  {
    return $this->headers;
  }

  /**
   * @param string $headers
   */
  public function setHeaders(string $headers): void
  {
    $this->headers = $headers;
  }

  /**
   * @return bool
   */
  public function isActive(): ?bool
  {
    return $this->active;
  }

  /**
   * @param bool $active
   */
  public function setActive(bool $active): void
  {
    $this->active = $active;
  }

}

<?php


namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Swagger\Annotations as SWG;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;

/**
 * ServiceGroup
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ServiceGroupRepository")
 * @ORM\Table(name="service_group",)
 *
 */
class ServiceGroup
{
  const DEFAULT_FOLDER_OBJECT = "%service_group% - Richiedente %applicant.completename.name% %applicant.completename.surname% (%applicant.fiscal_code.fiscal_code%)";


  /**
   * @ORM\Column(type="guid")
   * @ORM\Id
   * @SWG\Property(description="Service's uuid")
   */
  protected $id;

  /**
   * @var string
   *
   * @ORM\Column(type="string", length=255, unique=true)
   * @Assert\NotBlank(message="name")
   * @Assert\NotNull()
   * @SWG\Property(description="Service's name")
   */
  private $name;

  /**
   * @var string
   *
   * @Gedmo\Slug(fields={"name"})
   * @ORM\Column(type="string", length=255)
   * @SWG\Property(description="Human-readable unique identifier, if empty will be generated from service's name")
   */
  private $slug;

  /**
   * @var string
   * @ORM\Column(type="text", nullable=true)
   * @SWG\Property(description="Services group description")
   */
  private $description;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=true)
   * @SWG\Property(description="If selected the service group will be shown at the top of the page")
   */
  private $sticky;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=true)
   * @SWG\Property(description="Set true if application of  of this service group need to be registerd in folders")
   */
  private $registerInFolder;

  /**
   * @ORM\OneToMany(targetEntity="AppBundle\Entity\Servizio", mappedBy="serviceGroup", cascade={"persist"})
   * @Serializer\Exclude()
   */
  private $services;

  /**
   * @ORM\OneToMany(targetEntity="AppBundle\Entity\Pratica", mappedBy="serviceGroup", cascade={"persist"})
   * @Serializer\Exclude()
   */
  private $applications;

  /**
   * @var array
   * @ORM\Column(type="array", nullable=true)
   * @Serializer\Exclude()
   */
  private $additionalData;

  /**
   * ServiceGroup constructor.
   */
  public function __construct()
  {
    if (!$this->id) {
      $this->id = Uuid::uuid4();
    }
    $this->services = new ArrayCollection();
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
  public function getName(): ?string
  {
    return $this->name;
  }

  /**
   * @param string $name
   */
  public function setName(string $name)
  {
    $this->name = $name;
  }

  /**
   * @return string
   */
  public function getSlug(): ?string
  {
    return $this->slug;
  }

  /**
   * @param string $slug
   */
  public function setSlug(string $slug)
  {
    $this->slug = $slug;
  }

  /**
   * @return string
   */
  public function getDescription(): ?string
  {
    return $this->description;
  }

  /**
   * @param string $description
   */
  public function setDescription(string $description)
  {
    $this->description = $description;
  }

  /**
   * @return bool
   */
  public function isSticky()
  {
    return $this->sticky;
  }

  /**
   * @param bool $sticky
   * @return $this
   */
  public function setSticky( $sticky )
  {
    $this->sticky = $sticky;
    return $this;
  }

  /**
   * @return bool
   */
  public function isRegisterInFolder(): ?bool
  {
    return $this->registerInFolder;
  }

  /**
   * @param bool $registerInFolder
   */
  public function setRegisterInFolder(bool $registerInFolder)
  {
    $this->registerInFolder = $registerInFolder;
  }

  /**
   * @return mixed
   */
  public function getServices()
  {
    return $this->services;
  }

  /**
   * @return mixed
   */
  public function getPublicServices()
  {
    $result = new ArrayCollection();
    /** @var Servizio $service */
    foreach ($this->services as $service) {
      if ($service->getStatus() == Servizio::STATUS_AVAILABLE || $service->getStatus() == Servizio::STATUS_SUSPENDED || $service->getStatus() == Servizio::STATUS_SCHEDULED) {
        $result->add($service);
      }
    }
    return $result;
  }

  /**
   * @return mixed
   */
  public function getStickyServices()
  {
    $result = new ArrayCollection();
    /** @var Servizio $service */
    foreach ($this->services as $service) {
      // Only sticky services
      if ($service->isSticky() && $service->getStatus() !== Servizio::STATUS_CANCELLED) {
        /*
         * For all STICKY services
         * If service group is private (i.e. all services are private) show all services
         * show only not private services otherwise
         */
        if ((!$this->isPrivate() && $service->getStatus() !== Servizio::STATUS_PRIVATE) || $this->isPrivate() ) {
          $result->add($service);
        }
      }
    }
    return $result;
  }

  /**
   * @return mixed
   */
  public function getNotStickyServices()
  {
    $result = new ArrayCollection();
    /** @var Servizio $service */
    foreach ($this->services as $service) {
      /*
         * For all NOT STICKY services
         * If service group is private (i.e. all services are private) show all services
         * show only not private services otherwise
         */
      if (!$service->isSticky() && $service->getStatus() !== Servizio::STATUS_CANCELLED) {
        if ((!$this->isPrivate() && $service->getStatus() !== Servizio::STATUS_PRIVATE) || $this->isPrivate() ) {
          $result->add($service);
        }
      }
    }
    return $result;
  }

  public function isPrivate() {
    $private = true;
    /** @var Servizio $service */
    foreach ($this->services as $service) {
      if ($service->getStatus() !== Servizio::STATUS_PRIVATE) {
        $private = false;
      }
    }
    return $private;
  }

  /**
   * @param mixed $services
   */
  public function setServices($services)
  {
    $this->services = $services;
  }

  /**
   * @param Servizio $service
   */
  public function addService(Servizio $service)
  {
    if (!$this->services->contains($service)) {
      $this->services[] = $service;
    }
  }

  /**
   * @return mixed
   */
  public function getApplications()
  {
    return $this->applications;
  }

  /**
   * @param mixed $applications
   */
  public function setApplications($applications)
  {
    $this->applications = $applications;
  }

  /**
   * @return array
   */
  public function getAdditionalData()
  {
    return $this->additionalData;
  }

  /**
   * @param array $additionalData
   * @return ServiceGroup
   */
  public function setAdditionalData(array $additionalData): ServiceGroup
  {
    $this->additionalData = $additionalData;
    return $this;
  }

}

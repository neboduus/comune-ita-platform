<?php


namespace AppBundle\Dto;

use AppBundle\Entity\CPSUser;
use AppBundle\Model\IdCard;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use JMS\Serializer\Annotation\Groups;

class User
{

  /**
   * @Serializer\Type("string")
   * @SWG\Property(description="User's uuid")
   * @Groups({"read"})
   */
  protected $id;

  /**
   * @var string
   *
   * @Assert\NotBlank(message="This field is mandatory: codice_fiscale")
   * @Assert\NotNull(message="This field is mandatory: codice_fiscale")
   * @Serializer\Type("string")
   * @SWG\Property(description="User's fiscal code")
   * @Groups({"read", "write"})
   */
  private $codiceFiscale;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @SWG\Property(description="User's name")
   * @Groups({"read", "write"})
   */
  private $nome;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @SWG\Property(description="User's surname")
   * @Groups({"read", "write"})
   */
  private $cognome;

  /**
   * @var \DateTime
   *
   * @Serializer\Type("DateTime")
   * @SWG\Property(description="User's birth day yyyy-mm-dd")
   * @Groups({"read", "write"})
   */
  private $dataNascita;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @SWG\Property(description="User's birth place")
   * @Groups({"read", "write"})
   */
  private $luogoNascita;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @SWG\Property(description="User's surname birth code")
   * @Groups({"read", "write"})
   */
  private $codiceNascita;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @SWG\Property(description="User's birth province")
   * @Groups({"read", "write"})
   */
  private $provinciaNascita;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @SWG\Property(description="User's birth nation")
   * @Groups({"read", "write"})
   */
  private $statoNascita;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @SWG\Property(description="User's gender")
   * @Groups({"read", "write"})
   */
  private $sesso;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @SWG\Property(description="User's phone")
   * @Groups({"read", "write"})
   */
  private $telefono;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @SWG\Property(description="User's mobile phone")
   * @Groups({"read", "write"})
   */
  private $cellulare;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @SWG\Property(description="User's email")
   * @Groups({"read", "write"})
   */
  private $email;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @SWG\Property(description="User's address")
   * @Groups({"read", "write"})
   */
  private $indirizzoDomicilio;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @SWG\Property(description="User's zip code")
   * @Groups({"read", "write"})
   */
  private $capDomicilio;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @SWG\Property(description="User's city")
   * @Groups({"read", "write"})
   */
  private $cittaDomicilio;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @SWG\Property(description="User's province")
   * @Groups({"read", "write"})
   */
  private $provinciaDomicilio;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @SWG\Property(description="User's nation")
   * @Groups({"read", "write"})
   */
  private $statoDomicilio;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @SWG\Property(description="User's address")
   * @Groups({"read", "write"})
   */
  private $indirizzoResidenza;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @SWG\Property(description="User's zip code")
   * @Groups({"read", "write"})
   */
  private $capResidenza;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @SWG\Property(description="User's city")
   * @Groups({"read", "write"})
   */
  private $cittaResidenza;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @SWG\Property(description="User's province")
   * @Groups({"read", "write"})
   */
  private $provinciaResidenza;

  /**
   * @var string
   *
   * @Serializer\Type("string")
   * @SWG\Property(description="User's nation")
   * @Groups({"read", "write"})
   */
  private $statoResidenza;

  /**
   * @var IdCard
   * @SWG\Property(type="object", description="User's document", ref=@Model(type=IdCard::class))
   * @Serializer\Exclude()
   */
  private $idCard;

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
  public function getCodiceFiscale()
  {
    return $this->codiceFiscale;
  }

  /**
   * @param string $codiceFiscale
   */
  public function setCodiceFiscale($codiceFiscale)
  {
    $this->codiceFiscale = $codiceFiscale;
  }

  /**
   * @return string
   */
  public function getNome()
  {
    return $this->nome;
  }

  /**
   * @param string $nome
   */
  public function setNome($nome)
  {
    $this->nome = $nome;
  }

  /**
   * @return string
   */
  public function getCognome()
  {
    return $this->cognome;
  }

  /**
   * @param string $cognome
   */
  public function setCognome($cognome)
  {
    $this->cognome = $cognome;
  }

  /**
   * @return \DateTime
   */
  public function getDataNascita(): ?\DateTime
  {
    return $this->dataNascita;
  }

  /**
   * @param \DateTime $dataNascita
   */
  public function setDataNascita(?\DateTime $dataNascita): void
  {
    $this->dataNascita = $dataNascita;
  }

  /**
   * @return string
   */
  public function getLuogoNascita(): ?string
  {
    return $this->luogoNascita;
  }

  /**
   * @param string $luogoNascita
   */
  public function setLuogoNascita(?string $luogoNascita): void
  {
    $this->luogoNascita = $luogoNascita;
  }

  /**
   * @return string
   */
  public function getCodiceNascita(): ?string
  {
    return $this->codiceNascita;
  }

  /**
   * @param string $codiceNascita
   */
  public function setCodiceNascita(?string $codiceNascita): void
  {
    $this->codiceNascita = $codiceNascita;
  }

  /**
   * @return string
   */
  public function getProvinciaNascita(): ?string
  {
    return $this->provinciaNascita;
  }

  /**
   * @param string $provinciaNascita
   */
  public function setProvinciaNascita(?string $provinciaNascita): void
  {
    $this->provinciaNascita = $provinciaNascita;
  }

  /**
   * @return string
   */
  public function getStatoNascita(): ?string
  {
    return $this->statoNascita;
  }

  /**
   * @param string $statoNascita
   */
  public function setStatoNascita(?string $statoNascita): void
  {
    $this->statoNascita = $statoNascita;
  }

  /**
   * @return string
   */
  public function getSesso(): ?string
  {
    return $this->sesso;
  }

  /**
   * @param string $sesso
   */
  public function setSesso(?string $sesso): void
  {
    $this->sesso = $sesso;
  }

  /**
   * @return string
   */
  public function getTelefono(): ?string
  {
    return $this->telefono;
  }

  /**
   * @param string $telefono
   */
  public function setTelefono(?string $telefono): void
  {
    $this->telefono = $telefono;
  }

  /**
   * @return string
   */
  public function getCellulare(): ?string
  {
    return $this->cellulare;
  }

  /**
   * @param string $cellulare
   */
  public function setCellulare(?string $cellulare): void
  {
    $this->cellulare = $cellulare;
  }

  /**
   * @return string
   */
  public function getEmail(): ?string
  {
    return $this->email;
  }

  /**
   * @param string $email
   */
  public function setEmail(?string $email): void
  {
    $this->email = $email;
  }

  /**
   * @return string
   */
  public function getIndirizzoDomicilio(): ?string
  {
    return $this->indirizzoDomicilio;
  }

  /**
   * @param string $indirizzoDomicilio
   */
  public function setIndirizzoDomicilio(?string $indirizzoDomicilio): void
  {
    $this->indirizzoDomicilio = $indirizzoDomicilio;
  }

  /**
   * @return string
   */
  public function getCapDomicilio(): ?string
  {
    return $this->capDomicilio;
  }

  /**
   * @param string $capDomicilio
   */
  public function setCapDomicilio(?string $capDomicilio): void
  {
    $this->capDomicilio = $capDomicilio;
  }

  /**
   * @return string
   */
  public function getCittaDomicilio(): ?string
  {
    return $this->cittaDomicilio;
  }

  /**
   * @param string $cittaDomicilio
   */
  public function setCittaDomicilio(?string $cittaDomicilio): void
  {
    $this->cittaDomicilio = $cittaDomicilio;
  }

  /**
   * @return string
   */
  public function getProvinciaDomicilio(): ?string
  {
    return $this->provinciaDomicilio;
  }

  /**
   * @param string $provinciaDomicilio
   */
  public function setProvinciaDomicilio(?string $provinciaDomicilio): void
  {
    $this->provinciaDomicilio = $provinciaDomicilio;
  }

  /**
   * @return string
   */
  public function getStatoDomicilio(): ?string
  {
    return $this->statoDomicilio;
  }

  /**
   * @param string $statoDomicilio
   */
  public function setStatoDomicilio(?string $statoDomicilio): void
  {
    $this->statoDomicilio = $statoDomicilio;
  }

  /**
   * @return string
   */
  public function getIndirizzoResidenza(): ?string
  {
    return $this->indirizzoResidenza;
  }

  /**
   * @param string $indirizzoResidenza
   */
  public function setIndirizzoResidenza(?string $indirizzoResidenza): void
  {
    $this->indirizzoResidenza = $indirizzoResidenza;
  }

  /**
   * @return string
   */
  public function getCapResidenza(): ?string
  {
    return $this->capResidenza;
  }

  /**
   * @param string $capResidenza
   */
  public function setCapResidenza(?string $capResidenza): void
  {
    $this->capResidenza = $capResidenza;
  }

  /**
   * @return string
   */
  public function getCittaResidenza(): ?string
  {
    return $this->cittaResidenza;
  }

  /**
   * @param string $cittaResidenza
   */
  public function setCittaResidenza(?string $cittaResidenza): void
  {
    $this->cittaResidenza = $cittaResidenza;
  }

  /**
   * @return string
   */
  public function getProvinciaResidenza(): ?string
  {
    return $this->provinciaResidenza;
  }

  /**
   * @param string $provinciaResidenza
   */
  public function setProvinciaResidenza(?string $provinciaResidenza): void
  {
    $this->provinciaResidenza = $provinciaResidenza;
  }

  /**
   * @return string
   */
  public function getStatoResidenza(): ?string
  {
    return $this->statoResidenza;
  }

  /**
   * @param string $statoResidenza
   */
  public function setStatoResidenza(?string $statoResidenza): void
  {
    $this->statoResidenza = $statoResidenza;
  }

  /**
   * @return IdCard
   */
  public function getIdCard(): IdCard
  {
    return $this->idCard;
  }

  /**
   * @param IdCard $idCard
   */
  public function setIdCard(IdCard $idCard): void
  {
    $this->idCard = $idCard;
  }

  /**
   * @param CPSUser $user
   * @return User
   */
  public static function fromEntity(CPSUser $user)
  {
    $dto = new self();
    $dto->id = $user->getId();
    $dto->codiceFiscale = $user->getCodiceFiscale();
    $dto->nome = $user->getNome();
    $dto->cognome = $user->getCognome();
    $dto->dataNascita = $user->getDataNascita();
    $dto->luogoNascita = $user->getLuogoNascita();
    $dto->codiceNascita = $user->getCodiceNascita();
    $dto->provinciaNascita = $user->getProvinciaNascita();
    $dto->statoNascita = $user->getStatoNascita();
    $dto->sesso = $user->getSesso();
    $dto->telefono = $user->getTelefono();
    $dto->cellulare = $user->getCellulare();
    $dto->email = $user->getEmail();
    $dto->indirizzoDomicilio = $user->getIndirizzoDomicilio();
    $dto->capDomicilio = $user->getCapDomicilio();
    $dto->cittaDomicilio = $user->getCittaDomicilio();
    $dto->provinciaDomicilio = $user->getProvinciaDomicilio();
    $dto->statoDomicilio = $user->getStatoDomicilio();
    $dto->indirizzoResidenza = $user->getIndirizzoResidenza();
    $dto->capResidenza = $user->getCapResidenza();
    $dto->cittaResidenza = $user->getCittaResidenza();
    $dto->provinciaResidenza = $user->getProvinciaResidenza();
    $dto->statoResidenza = $user->getStatoResidenza();
    //$dto->idCard = $user->getIdCard();

    return $dto;
  }

  /**
   * @param CPSUser|null $entity
   * @return CPSUser
   */
  public function toEntity(CPSUser $entity = null)
  {
    if (!$entity) {
      $entity = new CPSUser();
    }
    $entity->setCodiceFiscale($this->codiceFiscale);
    $entity->setUsername($this->codiceFiscale ? $this->codiceFiscale : $this->getId());
    $entity->setNome($this->nome ? $this->nome : substr($this->codiceFiscale, 3, 3));
    $entity->setCognome($this->cognome ? $this->cognome : substr($this->codiceFiscale, 0, 3));

    $entity->setDataNascita($this->dataNascita);
    $entity->setLuogoNascita($this->luogoNascita);
    $entity->setCodiceNascita($this->codiceNascita);
    $entity->setProvinciaNascita($this->provinciaNascita);
    $entity->setStatoNascita($this->statoNascita);
    $entity->setSesso($this->sesso);

    $this->setTelefono($this->telefono);
    $entity->setCellulareContatto($this->cellulare);
    $entity->setEmail($this->email);
    $entity->setEmailContatto($this->email);

    $entity->setCpsIndirizzoDomicilio($this->indirizzoDomicilio);
    $entity->setCpsCapDomicilio($this->capDomicilio);
    $entity->setSdcCittaDomicilio($this->cittaDomicilio);
    $entity->setCpsProvinciaDomicilio($this->provinciaDomicilio);
    $entity->setCpsStatoDomicilio($this->statoDomicilio);

    $entity->setCpsIndirizzoResidenza($this->indirizzoResidenza);
    $entity->setCpsCapResidenza($this->capResidenza);
    $entity->setSdcCittaResidenza($this->cittaResidenza);
    $entity->setCpsProvinciaResidenza($this->provinciaResidenza);
    $entity->setCpsStatoResidenza($this->statoResidenza);

    return $entity;
  }


}

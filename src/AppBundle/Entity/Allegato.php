<?php

namespace AppBundle\Entity;

use AppBundle\Validator\Constraints as SDCAssert;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Class Allegato
 * @package AppBundle\Entity
 * @ORM\Entity
 * @ORM\Table(name="allegato")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"default" = "Allegato", "modulo_compilato" = "ModuloCompilato", "allegato_operatore" = "AllegatoOperatore", "risposta_operatore" = "RispostaOperatore", "allegato_scia" = "AllegatoScia", "richiesta_integrazione" = "RichiestaIntegrazione", "risposta_integrazione" = "RispostaIntegrazione", "integrazione" = "Integrazione", "ritiro" = "Ritiro", "messaggio" = "AllegatoMessaggio"})
 * @ORM\HasLifecycleCallbacks
 * @Vich\Uploadable()
 * @SDCAssert\ValidMimeType
 */
class Allegato implements AllegatoInterface
{

  /**
   * Hook timestampable behavior
   * updates createdAt, updatedAt fields
   */
  use TimestampableEntity;

  const TYPE_DEFAULT = 'default';
  const DEFAULT_DESCRIPTION = 'Allegato';
  const DEFAULT_MIME_TYPE = 'application/octet-stream';

  /**
   * @var string
   * @ORM\Column(type="guid")
   * @ORM\Id()
   */
  private $id;

  /**
   * @var string
   */
  protected $type;

  /**
   * @var File
   * @Vich\UploadableField(mapping="allegato", fileNameProperty="filename", size="fileSize", mimeType="mimeType")
   */
  private $file;

  /**
   * @var string
   * @ORM\Column(type="string")
   */
  private $filename;

  /**
   * @var string
   * @ORM\Column(type="string")
   */
  private $originalFilename;

  /**
   * @var string
   * @ORM\Column(type="text")
   */
  private $description;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   */
  private $numeroProtocollo;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @var string
   */
  private $idDocumentoProtocollo;

  /**
   * @ORM\Column(type="integer", name="protocol_time", nullable=true)
   */
  private $protocolTime;

  /**
   * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Pratica", mappedBy="allegati")
   * @var ArrayCollection
   */
  private   $pratiche;

  /**
   * @ORM\ManyToOne(targetEntity="AppBundle\Entity\CPSUser")
   * @var CPSUser
   */
  private $owner;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   */
  private $hash;

  /**
   * @var bool
   * @ORM\Column(type="boolean", nullable=true, options={"default":"1"})
   */
  private $protocolRequired;

  /**
   * @ORM\Column(type="decimal", precision=14, scale=2, nullable=true)
   */
  private $fileSize;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   */
  private $fileHash;

  /**
   * @var DateTime
   * @ORM\Column(type="datetime", nullable=true)
   */
  protected $expireDate;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   */
  private $mimeType;

  /**
   * @ORM\Column(type="json", options={"jsonb":true})
   * @var \JsonSerializable
   */
  protected $payload;

  /**
   * Allegato constructor.
   */
  public function __construct()
  {
    $this->id = Uuid::uuid4();
    $this->type = self::TYPE_DEFAULT;
    $this->createdAt = new DateTime('now', new \DateTimeZone('Europe/Rome'));
    $this->updatedAt = new DateTime('now', new \DateTimeZone('Europe/Rome'));
    $this->pratiche = new ArrayCollection();
    $this->protocolRequired = true;
  }

  /**
   * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
   * of 'UploadedFile' is injected into this setter to trigger the  update. If this
   * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
   * must be able to accept an instance of 'File' as the bundle will inject one here
   * during Doctrine hydration.
   *
   * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $file
   *
   * @return AllegatoInterface
   * @throws \Exception
   */
  public function setFile(File $file = null): AllegatoInterface
  {
    $this->file = $file;

    if ($file) {
      $this->updatedAt = new DateTime('now', new \DateTimeZone('Europe/Rome'));
      if ($file instanceof UploadedFile) {
        $this->originalFilename = $file->getClientOriginalName();
      }
    }

    return $this;
  }

  /**
   * @return File
   */
  public function getFile()
  {
    return $this->file;
  }

  /**
   * @return string
   */
  public function getId(): string
  {
    return $this->id;
  }

  /**
   * @return string
   */
  public function getFilename()
  {
    return $this->filename;
  }

  /**
   * @param string $filename
   * @return Allegato
   */
  public function setFilename($filename): AllegatoInterface
  {
    $this->filename = $filename;

    return $this;
  }

  /**
   * @return string
   */
  public function getDescription()
  {
    return $this->description;
  }

  /**
   * @param string $description
   * @return AllegatoInterface
   */
  public function setDescription($description): AllegatoInterface
  {
    $this->description = $description;

    return $this;
  }

  /**
   * @return string|null
   */
  public function getNumeroProtocollo()
  {
    return $this->numeroProtocollo;
  }

  /**
   * @param string $numeroProtocollo
   * @return AllegatoInterface
   */
  public function setNumeroProtocollo($numeroProtocollo)
  {
    $this->numeroProtocollo = $numeroProtocollo;
    return $this;
  }

  /**
   * @return string
   */
  public function getIdDocumentoProtocollo()
  {
    return $this->idDocumentoProtocollo;
  }

  /**
   * @param string $idDocumentoProtocollo
   * @return Allegato
   */
  public function setIdDocumentoProtocollo($idDocumentoProtocollo)
  {
    $this->idDocumentoProtocollo = $idDocumentoProtocollo;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getProtocolTime()
  {
    return $this->protocolTime;
  }

  /**
   * @param integer $time
   *
   * @return $this
   */
  public function setProtocolTime($time)
  {
    $this->protocolTime = $time;

    return $this;
  }

  /**
   * @return ArrayCollection
   */
  public function getPratiche(): Collection
  {
    return $this->pratiche;
  }

  /**
   * @param Pratica $pratica
   * @return $this
   */
  public function addPratica(Pratica $pratica)
  {
    if (!$this->pratiche->contains($pratica)) {
      $this->pratiche->add($pratica);
    }

    return $this;
  }

  /**
   * @param Pratica $pratica
   * @return $this
   */
  public function removePratica(Pratica $pratica)
  {
    if ($this->pratiche->contains($pratica)) {
      $this->pratiche->removeElement($pratica);
    }

    return $this;
  }

  /**
   * @return CPSUser
   */
  public function getOwner(): ?CPSUser
  {
    return $this->owner;
  }

  /**
   * @param $owner
   * @return $this
   */
  public function setOwner(CPSUser $owner)
  {
    $this->owner = $owner;

    return $this;
  }

  /**
   * @return string
   */
  public function getOriginalFilename()
  {
    return $this->originalFilename;
  }

  /**
   * @param string $originalFilename
   * @return $this
   */
  public function setOriginalFilename($originalFilename)
  {
    $this->originalFilename = $originalFilename;

    return $this;
  }

  public function getChoiceLabel(): string
  {
    return $this->originalFilename . '( ' . $this->description . ' )';
  }

  /**
   * @return string
   */
  public function getType(): string
  {
    return $this->type ?? '';
  }

  /**
   * @param string $type
   * @return Allegato
   */
  public function setType($type)
  {
    $this->type = $type;
    return $this;
  }

  public function getName()
  {
    return $this->getOriginalFilename();
  }

  function __toString()
  {
    return (string)$this->getId();
  }

  /**
   * @return string
   */
  public function getHash()
  {
    return $this->hash;
  }

  /**
   * @param string $hash
   * @return Allegato
   */
  public function setHash(string $hash)
  {
    $this->hash = $hash;
    return $this;
  }

  /**
   * @return bool
   */
  public function isProtocolRequired(): ?bool
  {
    return $this->protocolRequired;
  }

  /**
   * @param bool $protocolRequired
   */
  public function setProtocolRequired(?bool $protocolRequired): void
  {
    $this->protocolRequired = $protocolRequired;
  }

  /**
   * @return mixed
   */
  public function getFileSize()
  {
    return $this->fileSize;
  }

  /**
   * @param mixed $fileSize
   */
  public function setFileSize($fileSize): void
  {
    $this->fileSize = $fileSize;
  }

  /**
   * @return string
   */
  public function getFileHash(): string
  {
    return $this->fileHash;
  }

  /**
   * @param string $fileHash
   */
  public function setFileHash(?string $fileHash): void
  {
    $this->fileHash = $fileHash;
  }

  /**
   * @return DateTime
   */
  public function getExpireDate()
  {
    return $this->expireDate;
  }

  /**
   * @param DateTime|null $expireDate
   */
  public function setExpireDate(?DateTime $expireDate)
  {
    $this->expireDate = $expireDate;
  }

  /**
   * @return string
   */
  public function getMimeType(): ?string
  {
    return $this->mimeType;
  }

  /**
   * @param string $mimeType
   */
  public function setMimeType(?string $mimeType): void
  {
    $this->mimeType = $mimeType;
  }

  /**
   * @return mixed
   */
  public function getPayload()
  {
    return $this->payload;
  }

  /**
   * @param $payload
   */
  public function setPayload($payload)
  {
    $this->payload = $payload;
  }

  /**
   * @return string
   */
  public function getHumanReadableFileSize()
  {
    try{
      $bytes = $this->getFile()->getSize();

      $bytes = floatval($bytes);
      $arBytes = array(
        0 => array(
          "UNIT" => "TB",
          "VALUE" => pow(1024, 4),
        ),
        1 => array(
          "UNIT" => "GB",
          "VALUE" => pow(1024, 3),
        ),
        2 => array(
          "UNIT" => "MB",
          "VALUE" => pow(1024, 2),
        ),
        3 => array(
          "UNIT" => "KB",
          "VALUE" => 1024,
        ),
        4 => array(
          "UNIT" => "B",
          "VALUE" => 1,
        ),
      );

      foreach ($arBytes as $arItem) {
        if ($bytes >= $arItem["VALUE"]) {
          $result = $bytes / $arItem["VALUE"];
          $result = str_replace(".", ",", strval(round($result, 2)))." ".$arItem["UNIT"];
          break;
        }
      }

      return $result;

    }catch (\Exception $e){

      return '';
    }
  }
}

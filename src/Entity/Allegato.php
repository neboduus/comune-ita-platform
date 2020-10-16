<?php

namespace App\Entity;

use App\Validator\Constraints as SDCAssert;
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
 * @package App\Entity
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
   * @Vich\UploadableField(mapping="allegato", fileNameProperty="filename")
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
   * @ORM\ManyToMany(targetEntity="App\Entity\Pratica", mappedBy="allegati")
   * @var ArrayCollection
   */
  private   $pratiche;

  /**
   * @ORM\ManyToOne(targetEntity="App\Entity\CPSUser")
   * @var CPSUser
   */
  private $owner;

  /**
   * @var string
   * @ORM\Column(type="string", nullable=true)
   */
  private $hash;

  /**
   * Allegato constructor.
   */
  public function __construct()
  {
    $this->id = Uuid::uuid4();
    $this->type = self::TYPE_DEFAULT;
    $this->createdAt = new \DateTime('now', new \DateTimeZone('Europe/Rome'));
    $this->updatedAt = new \DateTime('now', new \DateTimeZone('Europe/Rome'));
    $this->pratiche = new ArrayCollection();
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
   */
  public function setFile(File $file = null): AllegatoInterface
  {
    $this->file = $file;

    if ($file) {
      $this->updatedAt = new \DateTime('now', new \DateTimeZone('Europe/Rome'));
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
  public function setNumeroProtocollo(string $numeroProtocollo)
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
  public function setIdDocumentoProtocollo(string $idDocumentoProtocollo)
  {
    $this->idDocumentoProtocollo = $idDocumentoProtocollo;
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
    return $this->getDescription() . ' ' . ' ' . $this->getOriginalFilename();
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

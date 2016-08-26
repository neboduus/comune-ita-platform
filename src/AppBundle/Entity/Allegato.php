<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Class Allegato
 * @package AppBundle\Entity
 * @ORM\Entity
 * @ORM\Table(name="allegato")
 * @ORM\HasLifecycleCallbacks
 * @Vich\Uploadable()
 */
class Allegato
{
    /**
     * @var string
     * @ORM\Column(type="guid")
     * @ORM\Id()
     */
    private $id;

    /**
     * @var File
     * @Vich\UploadableField(mapping="allegato", fileNameProperty="filename")
     * @
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
     * @ORM\Column(type="string")
     */
    private $description;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetimetz")
     */
    private $updatedAt;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $numeroProtocollo;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Pratica", inversedBy="allegati")
     * @var Pratica
     */
    private $pratica;

    /**
     * Allegato constructor.
     */
    public function __construct()
    {
        $this->id = Uuid::uuid4();
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('Europe/Rome'));
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
     * @return Allegato
     */
    public function setFile(File $file = null):Allegato
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
    public function setFilename($filename): Allegato
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
     * @return Allegato
     */
    public function setDescription($description): Allegato
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return Allegato
     */
    public function setUpdatedAt(\DateTime $updatedAt): Allegato
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getNumeroProtocollo()
    {
        return $this->numeroProtocollo;
    }

    /**
     * @param string $numeroProtocollo
     * @return $this
     */
    public function setNumeroProtocollo($numeroProtocollo)
    {
        $this->numeroProtocollo = $numeroProtocollo;

        return $this;
    }

    /**
     * @ORM\PreFlush()
     */
    public function updateNumeriProtocolloPratica()
    {
        if ($this->numeroProtocollo != null) {
            $this->pratica->addNumeroDiProtocollo($this->numeroProtocollo);
        }
    }

    /**
     * @return Pratica
     */
    public function getPratica(): Pratica
    {
        return $this->pratica;
    }

    /**
     * @param Pratica $pratica
     * @return $this
     */
    public function setPratica(Pratica $pratica)
    {
        $this->pratica = $pratica;

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

    /**
     * @param ExecutionContextInterface $context
     * @Assert\Callback()
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if ($this->file == null || ! in_array($this->file->getMimeType(), array(
            'image/jpeg',
            'image/gif',
            'image/png',
            'application/postscript',
            'application/pdf',
        ))) {
            $context
                ->buildViolation('Wrong file type (jpg,gif,png,mp4,mov,avi)')
                ->atPath('file')
                ->addViolation()
            ;
        }
    }
}

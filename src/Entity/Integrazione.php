<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Integrazione
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Integrazione extends Allegato
{
    const TYPE_DEFAULT = 'integrazione';

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $idDocumentoProtocollo;

    /**
     * @ORM\Column(type="array", nullable=true)
     * @var ArrayCollection
     */
    private $numeriProtocollo;


    /**
     * Integrazione constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = 'integrazione';
        $this->numeriProtocollo = new ArrayCollection();
    }


    /**
     * @return string|null
     */
    public function getIdDocumentoProtocollo()
    {
        return $this->idDocumentoProtocollo;
    }

    /**
     * @param string $idDocumentoProtocollo
     * @return Integrazione
     */
    public function setIdDocumentoProtocollo(string $idDocumentoProtocollo)
    {
        $this->idDocumentoProtocollo = $idDocumentoProtocollo;
        return $this;
    }

    /**
     * @param array $numeroDiProtocollo
     *
     * @return Integrazione
     */
    public function addNumeroDiProtocollo($numeroDiProtocollo)
    {
        if (!$this->numeriProtocollo->contains($numeroDiProtocollo)) {
            $this->numeriProtocollo->add($numeroDiProtocollo);
        }

        return $this;
    }

    /**
     * @ORM\PreFlush()
     */
    public function arrayToJson()
    {
        $this->numeriProtocollo = json_encode($this->getNumeriProtocollo()->toArray());
    }

    /**
     * @return mixed
     */
    public function getNumeriProtocollo()
    {
        if (!$this->numeriProtocollo instanceof ArrayCollection) {
            $this->jsonToArray();
        }

        return $this->numeriProtocollo;
    }

    /**
     * @ORM\PostLoad()
     * @ORM\PostUpdate()
     */
    public function jsonToArray()
    {
        $this->numeriProtocollo = new ArrayCollection(json_decode($this->numeriProtocollo));
    }

    public function getType(): string
    {
        return self::TYPE_DEFAULT;
    }
}

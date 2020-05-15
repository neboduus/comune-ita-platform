<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class RichiestaIntegrazione extends Allegato
{
    const STATUS_PENDING = 1000;
    const STATUS_DONE = 2000;
    const TYPE_DEFAULT = 'richiesta_integrazione';

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $idDocumentoProtocollo;

    /**
     * @ORM\Column(type="json_array", options={"jsonb":true})
     * @var \JsonSerializable
     */
    private $payload;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Pratica", inversedBy="richiesteIntegrazione")
     * @var Pratica $praticaPerCuiServeIntegrazione
     */
    private $praticaPerCuiServeIntegrazione;

    /**
     * @ORM\Column(type="integer")
     * @var integer
     */
    private $status;

    public function __construct()
    {
        parent::__construct();
        $this->type = self::TYPE_DEFAULT;
        $this->status = self::STATUS_PENDING;
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
     * @return RichiestaIntegrazione
     */
    public function setIdDocumentoProtocollo(string $idDocumentoProtocollo)
    {
        $this->idDocumentoProtocollo = $idDocumentoProtocollo;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param string $payload
     *
     * @return RichiestaIntegrazione
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * @return Pratica|null
     */
    public function getPratica()
    {
        return $this->praticaPerCuiServeIntegrazione;
    }

    /**
     * @param Pratica $pratica
     *
     * @return $this
     */
    public function setPratica(Pratica $pratica)
    {
        $this->praticaPerCuiServeIntegrazione = $pratica;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     *
     * @return RichiestaIntegrazione
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return RichiestaIntegrazione
     */
    public function markAsDone()
    {
        return $this->setStatus(self::STATUS_DONE);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
      return self::TYPE_DEFAULT;
    }
}

<?php


namespace App\Model;

use JMS\Serializer\Annotation as Serializer;

class AdditionalData
{

    // {"urlModuloPrincipale":"","urlModuliAggiuntivi":[], "formio_id": "5d8c636c1953a2002b976f52"}

    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $urlModuloPrincipale;


    /**
     * @var string[]
     * @Serializer\Type("array")
     */
    private $urlModuliAggiuntivi;

    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $formioId;

    /**
     * @return string
     */
    public function getUrlModuloPrincipale(): string
    {
        return $this->urlModuloPrincipale;
    }

    /**
     * @param string $urlModuloPrincipale
     */
    public function setUrlModuloPrincipale(string $urlModuloPrincipale)
    {
        $this->urlModuloPrincipale = $urlModuloPrincipale;
    }

    /**
     * @return string[]
     */
    public function getUrlModuliAggiuntivi(): array
    {
        return $this->urlModuliAggiuntivi;
    }

    /**
     * @param string[] $urlModuliAggiuntivi
     */
    public function setUrlModuliAggiuntivi(array $urlModuliAggiuntivi)
    {
        $this->urlModuliAggiuntivi = $urlModuliAggiuntivi;
    }

    /**
     * @return string
     */
    public function getFormioId(): string
    {
        return $this->formioId;
    }

    /**
     * @param string $formioId
     */
    public function setFormioId(string $formioId)
    {
        $this->formioId = $formioId;
    }
}

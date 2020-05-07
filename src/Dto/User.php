<?php

namespace App\Dto;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Swagger\Annotations as SWG;

class User
{

    /**
     * @Serializer\Type("string")
     * @SWG\Property(description="User's uuid")
     */
    protected $id;

    /**
     * @var string
     *
     * @Assert\NotBlank(message="This field is mandatory: codice_fiscale")
     * @Assert\NotNull(message="This field is mandatory: codice_fiscale")
     * @Serializer\Type("string")
     * @SWG\Property(description="User's fiscal code")
     */
    private $codiceFiscale;

    /**
     * @var string
     *
     * @Serializer\Type("string")
     * @SWG\Property(description="User's name")
     */
    private $nome;

    /**
     * @var string
     *
     * @Serializer\Type("string")
     * @SWG\Property(description="User's surname")
     */
    private $cognome;

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
     * @param \App\Entity\CPSUser $user
     * @return self
     */
    public static function fromEntity(\App\Entity\CPSUser $user)
    {
        $dto = new self();
        $dto->id = $user->getId();
        $dto->codiceFiscale = $user->getCodiceFiscale();
        $dto->nome = $user->getNome();
        $dto->cognome = $user->getCognome();

        return $dto;
    }

    /**
     * @param \App\Entity\CPSUser|null $entity
     * @return \App\Entity\CPSUser
     */
    public function toEntity(\App\Entity\CPSUser $entity = null)
    {
        if (!$entity) {
            $entity = new \App\Entity\CPSUser();
        }
        $entity->setCodiceFiscale($this->codiceFiscale);
        $entity->setUsername($this->codiceFiscale ? $this->codiceFiscale : $this->getId());
        $entity->setNome($this->nome ? $this->nome : substr($this->codiceFiscale, 3, 3));
        $entity->setCognome($this->cognome ? $this->cognome : substr($this->codiceFiscale, 0, 3));

        return $entity;
    }
}

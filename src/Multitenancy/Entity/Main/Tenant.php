<?php

namespace App\Multitenancy\Entity\Main;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="App\Multitenancy\Repository\TenantRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Tenant
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer", nullable=false)
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @Gedmo\Slug(fields={"name"})
     * @ORM\Column(type="string", length=100, unique=true)
     */
    private $slug;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $dbHost;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    private $dbPort;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $dbName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $dbUser;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $dbPassword;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $host;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $pathInfoPrefix;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $protocolloHandler;

    /**
     * @var string
     * @ORM\Column(type="string", unique=true)
     */
    private $codiceMeccanografico;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $logoUrl;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): Tenant
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDbHost(): ?string
    {
        return $this->dbHost;
    }

    public function setDbHost(string $dbHost): Tenant
    {
        $this->dbHost = $dbHost;

        return $this;
    }

    public function getDbPort(): ?int
    {
        return $this->dbPort;
    }

    public function setDbPort(int $dbPort): Tenant
    {
        $this->dbPort = $dbPort;

        return $this;
    }

    public function getDbName(): ?string
    {
        return $this->dbName;
    }

    public function setDbName(string $dbName): Tenant
    {
        $this->dbName = $dbName;

        return $this;
    }

    public function getDbUser(): ?string
    {
        return $this->dbUser;
    }

    public function setDbUser(string $dbUser): Tenant
    {
        $this->dbUser = $dbUser;

        return $this;
    }

    public function getDbPassword(): ?string
    {
        return $this->dbPassword;
    }

    public function setDbPassword(string $dbPassword): Tenant
    {
        $this->dbPassword = $dbPassword;

        return $this;
    }

    public function getHost(): string
    {
        return (string)$this->host;
    }

    public function setHost(string $host): Tenant
    {
        $this->host = $host;

        return $this;
    }

    public function hasPathInfoPrefix()
    {
        return !empty($this->pathInfoPrefix);
    }

    public function getPathInfoPrefix(): string
    {
        return (string)$this->pathInfoPrefix;
    }

    public function setPathInfoPrefix(string $pathInfoPrefix): Tenant
    {
        $this->pathInfoPrefix = $pathInfoPrefix;

        return $this;
    }

    /**
     * @return string
     */
    public function getProtocolloHandler(): string
    {
        return (string)$this->protocolloHandler;
    }

    /**
     * @param string $protocolloHandler
     * @return Tenant
     */
    public function setProtocolloHandler(string $protocolloHandler): Tenant
    {
        $this->protocolloHandler = $protocolloHandler;

        return $this;
    }

    /**
     * @return string
     */
    public function getCodiceMeccanografico(): string
    {
        return $this->codiceMeccanografico;
    }

    /**
     * @param string $codiceMeccanografico
     * @return Tenant
     */
    public function setCodiceMeccanografico(string $codiceMeccanografico): Tenant
    {
        $this->codiceMeccanografico = $codiceMeccanografico;

        return $this;
    }

    public function __toString()
    {
        return (string)$this->getSlug();
    }

    /**
     * @return string
     */
    public function getLogoUrl()
    {
        return $this->logoUrl;
    }

    /**
     * @param string $logoUrl
     */
    public function setLogoUrl($logoUrl): void
    {
        $this->logoUrl = $logoUrl;
    }

}

<?php

namespace App\Services;

use App\Entity\Ente;
use App\Multitenancy\TenantAwareInterface;
use App\Multitenancy\TenantAwareTrait;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\HttpKernel\KernelInterface;

class InstanceService implements TenantAwareInterface
{
    use TenantAwareTrait;

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var Ente
     */
    private $currentInstance;

    /**
     * @var PackageInterface
     */
    private $package;

    private $kernel;

    /**
     * InstanceService constructor.
     */
    public function __construct(ManagerRegistry $doctrine, KernelInterface $kernel)
    {
        $this->doctrine = $doctrine;
        $this->kernel = $kernel;
    }

    public function getPrefix()
    {
        return $this->hasTenant() ? $this->getTenant()->getPathInfoPrefix() : '';
    }

    public function getSlug()
    {
        return $this->hasTenant() ? $this->getTenant()->getSlug() : '';
    }

    /**
     * @return \App\Entity\Ente|bool
     */
    public function getCurrentInstance()
    {
        if ($this->currentInstance === null && $this->hasTenant()) {
            $repo = $this->doctrine->getRepository('App:Ente');
            $this->currentInstance = $repo->findOneBy(array('slug' => $this->getTenant()->getSlug()));
        }

        if ($this->currentInstance === null && $this->hasTenant()) {
            return (new Ente())
                ->setName($this->getTenant()->getName() . ' (ente non configurato)')
                ->setCodiceMeccanografico($this->getTenant()->getCodiceMeccanografico())
                ->setCodiceAmministrativo($this->getTenant()->getCodiceMeccanografico())
                ->setSiteUrl('?')
                ->setContatti('?')
                ->setEmail('?')
                ->setEmailCertificata('?');
        }

        return $this->currentInstance;
    }

    public function getMailSender()
    {
        return 'test';
    }

    public function getLogoUrl()
    {
        if ($this->hasTenant()) {
            if ($this->getTenant()->getLogoUrl()) {
                return $this->getTenant()->getLogoUrl();
            }

            $staticFile = '/stanzadelcittadino/images/logo-' . $this->getSlug() . '.png';
            if (file_exists($this->kernel->getProjectDir() . '/public' . $staticFile)){
                return $staticFile;
            }
        }

        return null;
    }
}

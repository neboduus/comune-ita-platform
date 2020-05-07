<?php

namespace App\DataFixtures\Main;

use App\DataFixtures\GoogleSpreadsheetTrait;
use App\Multitenancy\Entity\Main\Tenant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class TenantFixtures extends Fixture implements ContainerAwareInterface, FixtureGroupInterface
{
    use MainFixturesTrait;
    use GoogleSpreadsheetTrait;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $data = $this->getGoogleSpreadsheetData('Enti');
        $tenantDbParameters = $this->container->getParameter('tenant_db');

        foreach ($data as $item) {
            if (!empty($item['prefisso']) && !empty($item['db'])) {
                $dbName = $item['db'];

                $tenant = new Tenant();
                $tenant
                    ->setName($item['name'])
                    ->setDbHost($tenantDbParameters['host'])
                    ->setDbPort($tenantDbParameters['port'])
                    ->setDbname($dbName)
                    ->setDbUser($tenantDbParameters['user'])
                    ->setDbpassword($tenantDbParameters['password'])
                    ->setPathInfoPrefix($item['prefisso'])
                    ->setCodiceMeccanografico($item['codice'])
                    ->setProtocolloHandler($item['protocollo']);
                if (isset($item['logo'])){
                    $tenant->setLogoUrl('logo');
                }
                $manager->persist($tenant);
            }
        }

        $manager->flush();
    }
}

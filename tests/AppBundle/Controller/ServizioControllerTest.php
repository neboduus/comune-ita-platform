<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use AppBundle\Services\CPSUserProvider;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Response;
use Tests\AppBundle\Base\AppTestCase;

class ServizioControllerTest extends AppTestCase
{
    /**
     * @var CPSUserProvider
     */
    protected $userProvider;

    public function setUp()
    {
        parent::setUp();
        $this->userProvider = $this->container->get('ocsdc.cps.userprovider');
        $this->cleanDb(Pratica::class);
        $this->cleanDb(Servizio::class);
        $this->cleanDb(User::class);
    }

    public function testIndexAsLoggedInUser()
    {
        $user = $this->createCPSUser();

        $repo = $this->em->getRepository("AppBundle:Servizio");
        $servizio = new Servizio();
        $servizio->setName('Primo servizio');

        $this->em->persist($servizio);
        $this->em->flush();

        $serviceCountAfterInsert = count($repo->findAll());

        $crawler = $this->clientRequestAsCPSUser($user, 'GET', $this->router->generate('servizi_list'));
        $renderedServicesCount = $crawler->filter('.servizio')->count();
        $this->assertEquals( $serviceCountAfterInsert, $renderedServicesCount );
    }

    public function testICanSeeAServiceDetailAsLoggedInUser()
    {
        $user = $this->createCPSUser();

        $servizio = new Servizio();
        $servizio->setName('Secondo servizio');
        $this->em->persist($servizio);
        $this->em->flush();

        $servizioDetailUrl = $this->router->generate('servizi_show', ['slug' => $servizio->getSlug()], Router::ABSOLUTE_URL);

        $crawler = $this->clientRequestAsCPSUser($user, 'GET', $this->router->generate('servizi_list'));
        $detailLink = $crawler->selectLink('Secondo servizio')->link()->getUri();

        $this->assertEquals($servizioDetailUrl, $detailLink);

        $this->clientRequestAsCPSUser($user, 'GET', $detailLink);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testICanSeeAServiceApplicationLinkAsLoggedInUser()
    {
        $user = $this->createCPSUser();

        $servizio = new Servizio();
        $servizio->setName('Terzo servizio');
        $this->em->persist($servizio);
        $this->em->flush();

        $servizioDetailUrl = $this->router->generate('servizi_run', ['slug' => $servizio->getSlug()], Router::ABSOLUTE_URL);

        $crawler = $this->clientRequestAsCPSUser($user, 'GET', $this->router->generate('servizi_show', ['slug' => $servizio->getSlug()]));
        $detailLink = $crawler->selectLink($this->translator->trans('accedi_al_servizio', ['%name%' => $servizio->getName()]))->link()->getUri();
        $this->assertEquals($servizioDetailUrl, $detailLink);

    }
}

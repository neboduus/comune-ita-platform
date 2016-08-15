<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Ente;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use AppBundle\Services\CPSUserProvider;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Response;
use Tests\AppBundle\Base\AbstractAppTestCase;

/**
 * Class ServizioControllerTest
 */
class ServizioControllerTest extends AbstractAppTestCase
{
    /**
     * @var CPSUserProvider
     */
    protected $userProvider;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        $this->userProvider = $this->container->get('ocsdc.cps.userprovider');
        $this->em->getConnection()->executeQuery('DELETE FROM servizio_enti')->execute();
        $this->cleanDb(Pratica::class);
        $this->cleanDb(Servizio::class);
        $this->cleanDb(User::class);
        $this->cleanDb(Ente::class);
    }

    /**
     * @test
     */
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
        $this->assertEquals($serviceCountAfterInsert, $renderedServicesCount);
    }

    /**
     * @test
     */
    public function testICanSeeAServiceDetailAsLoggedInUser()
    {
        $user = $this->createCPSUser();

        $ente = new Ente();
        $ente->setName('Ente di prova');
        $this->em->persist($ente);
        $this->em->flush();

        $servizio = new Servizio();
        $servizio->setName('Secondo servizio')
            ->setEnti([$ente]);
        $this->em->persist($servizio);
        $this->em->flush();

        $servizioDetailUrl = $this->router->generate('servizi_show', ['slug' => $servizio->getSlug()], Router::ABSOLUTE_URL);

        $crawler = $this->clientRequestAsCPSUser($user, 'GET', $this->router->generate('servizi_list'));
        $detailLink = $crawler->selectLink('Secondo servizio')->link()->getUri();

        $this->assertEquals($servizioDetailUrl, $detailLink);

        $this->clientRequestAsCPSUser($user, 'GET', $detailLink);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function testICanSeeANewPraticaLinkInServiceDetailsAsLoggedInUser()
    {
        $user = $this->createCPSUser();

        $ente = new Ente();
        $ente->setName('Ente di prova');
        $this->em->persist($ente);
        $this->em->flush();

        $ente2 = new Ente();
        $ente2->setName('Ente di test');
        $this->em->persist($ente2);
        $this->em->flush();

        $servizio = new Servizio();
        $servizio->setName('Terzo servizio')->setEnti([$ente, $ente2]);
        $this->em->persist($servizio);
        $this->em->flush();

        $crawler = $this->clientRequestAsCPSUser($user, 'GET', $this->router->generate('servizi_show', ['slug' => $servizio->getSlug()], Router::ABSOLUTE_URL));

        $newPraticaUrl = $this->router->generate(
            'pratiche_new',
            ['servizio' => $servizio->getSlug()]
        );

        $this->assertContains($newPraticaUrl, $this->client->getResponse()->getContent(), 'Stringa non trovata');
    }

    /**
     * @test
     */
    public function testICanSeeServicesWithPendingProceduresInStickyAreaAsLoggedInUser()
    {
        $user = $this->createCPSUser();
        $this->setupPraticheForUser($user);

        $numberServices = rand(1, 10);

        for ($i = 1; $i <= $numberServices; $i++) {
            $servizio = new Servizio();
            $servizio->setName('Servizio '.$i);
            $this->em->persist($servizio);
            $this->em->flush();
        }

        $repo = $this->em->getRepository("AppBundle:Servizio");
        $serviceCountAfterInsert = count($repo->findAll());
        $crawler = $this->clientRequestAsCPSUser($user, 'GET', $this->router->generate('servizi_list'));
        $stickyRenderedServices = $crawler->filter('.sticky')->filter('.servizio');
        $stickyRenderedServicesCount = $stickyRenderedServices->count();
        $nonstickyRenderedServicesCount = $crawler->filter('.list')->filter('.servizio')->count();
        $this->assertEquals($serviceCountAfterInsert, $stickyRenderedServicesCount + $nonstickyRenderedServicesCount);

        $primoServizioSticky = $stickyRenderedServices->eq(0)->filter('a')->attr('href');
        $route = $this->router->match($primoServizioSticky);
        $servizioSlug = $route['slug'];
        $persistedServizio = $this->em->getRepository('AppBundle:Servizio')->findBySlug($servizioSlug);
        $pratichePendingPerServizio = $this->em->getRepository('AppBundle:Pratica')->findBy(['user' => $user, 'servizio' => $persistedServizio]);
        $this->assertGreaterThan(0, count($pratichePendingPerServizio));
        $this->assertEquals(Pratica::STATUS_PENDING, $pratichePendingPerServizio[0]->getStatus());
    }
}

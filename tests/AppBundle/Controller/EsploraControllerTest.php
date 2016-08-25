<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Ente;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use Tests\AppBundle\Base\AbstractAppTestCase;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Services\CPSUserProvider;

class EsploraControllerTest extends AbstractAppTestCase
{
    /**
     * @var CPSUserProvider
     */
    protected $userProvider;

    public function setUp()
    {
        parent::setUp();
        $this->userProvider = $this->container->get('ocsdc.cps.userprovider');
        $this->em->getConnection()->executeQuery('DELETE FROM servizio_enti')->execute();
        $this->em->getConnection()->executeQuery('DELETE FROM ente_asili')->execute();
        $this->cleanDb(Pratica::class);
        $this->cleanDb(Servizio::class);
        $this->cleanDb(Ente::class);
    }

    public function testICanSeeServiziAsAnonumousUser()
    {
        $repo = $this->em->getRepository("AppBundle:Servizio");
        $servizio = new Servizio();
        $servizio->setName('Primo servizio');

        $this->em->persist($servizio);
        $this->em->flush();

        $serviceCountAfterInsert = count($repo->findAll());

        $crawler = $this->client->request('GET', $this->router->generate('servizi_list'));
        $renderedServicesCount = $crawler->filter('.servizio')->count();
        $this->assertEquals( $serviceCountAfterInsert, $renderedServicesCount );
    }

    public function testICanSeeAServiceDetailAsAnonymousUser()
    {
        $servizio = new Servizio();
        $servizio->setName('Secondo servizio');
        $this->em->persist($servizio);
        $this->em->flush();

        $servizioDetailUrl = $this->router->generate('servizi_show', ['slug' => $servizio->getSlug()], Router::ABSOLUTE_URL);

        $crawler = $this->client->request('GET', '/servizi/');
        $detailLink = $crawler->selectLink('Secondo servizio')->link()->getUri();

        $this->assertEquals($servizioDetailUrl, $detailLink);

        $this->client->request('GET', $detailLink);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }
}

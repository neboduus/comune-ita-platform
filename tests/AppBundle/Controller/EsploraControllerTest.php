<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Servizio;
use Tests\AppBundle\Base\AppTestCase;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Response;

class EsploraControllerTest extends AppTestCase
{
    public function setUp(){
        parent::setUp();
        $this->cleanDb(Servizio::class);
    }

    public function testICanSeeServiziAsAnonumousUser()
    {
        $repo = $this->em->getRepository("AppBundle:Servizio");
        $servizio = new Servizio();
        $servizio->setName('Primo servizio');

        $this->em->persist($servizio);
        $this->em->flush();

        $serviceCountAfterInsert = count($repo->findAll());

        $crawler = $this->client->request('GET', $this->router->generate('esplora_servizi_list'));
        $renderedServicesCount = $crawler->filter('.servizio')->count();
        $this->assertEquals( $serviceCountAfterInsert, $renderedServicesCount );
    }

    public function testICanSeeAServiceDetailAsAnonymousUser()
    {
        $servizio = new Servizio();
        $servizio->setName('Secondo servizio');
        $this->em->persist($servizio);
        $this->em->flush();

        $servizioDetailUrl = $this->router->generate('esplora_servizi_show', ['slug' => $servizio->getSlug()], Router::ABSOLUTE_URL);

        $crawler = $this->client->request('GET', '/esplora/');
        $detailLink = $crawler->selectLink('Secondo servizio')->link()->getUri();

        $this->assertEquals($servizioDetailUrl, $detailLink);

        $this->client->request('GET', $detailLink);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertContains($this->translator->trans('registrati_per_accedere_al_servizio'), $this->client->getResponse()->getContent());

    }
}

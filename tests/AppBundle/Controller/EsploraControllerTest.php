<?php

namespace Tests\AppBundle\Controller;


use AppBundle\Entity\Servizio;
use Tests\AppBundle\Base\AppTestCase;

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

        $crawler = $this->client->request('GET', '/esplora/');
        $renderedServicesCount = $crawler->filter('.servizio')->count();
        $this->assertEquals( $serviceCountAfterInsert, $renderedServicesCount );
    }
}

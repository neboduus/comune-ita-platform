<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use Tests\AppBundle\Base\AppTestCase;

class ServizioControllerTest extends AppTestCase
{
    public function setUp(){
        parent::setUp();
        $this->cleanDb(Pratica::class);
        $this->cleanDb(Servizio::class);
        $this->cleanDb(User::class);
    }

    public function testIndex()
    {
        $user = $this->createUser();

        $repo = $this->em->getRepository("AppBundle:Servizio");
        $servizio = new Servizio();
        $servizio->setName('Primo servizio');

        $this->em->persist($servizio);
        $this->em->flush();

        $serviceCountAfterInsert = count($repo->findAll());

        $crawler = $this->client->request('GET', '/servizi/', [], [], ['HTTP_REMOTE_USER' => $user->getName()]);
        $renderedServicesCount = $crawler->filter('.servizio')->count();
        $this->assertEquals( $serviceCountAfterInsert, $renderedServicesCount );

    }
}

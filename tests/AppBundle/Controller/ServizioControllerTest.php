<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\VarDumper;
use Tests\AppBundle\Base\AppTestCase;

class ServizioControllerTest extends AppTestCase
{
    public function setUp(){
        parent::setUp();
        $this->cleanDb(Servizio::class);
        $this->cleanDb(User::class);
    }

    public function testIndexAsLoggedInUser()
    {
        $user = $this->createUser();

        $repo = $this->em->getRepository("AppBundle:Servizio");
        $servizio = new Servizio();
        $servizio->setName('Primo servizio');

        $this->em->persist($servizio);
        $this->em->flush();

        $serviceCountAfterInsert = count($repo->findAll());

        $crawler = $this->client->request('GET', $this->router->generate('servizi_list'), [], [], ['HTTP_REMOTE_USER' => $user->getName()]);
        $renderedServicesCount = $crawler->filter('.servizio')->count();
        $this->assertEquals( $serviceCountAfterInsert, $renderedServicesCount );
    }

    public function testICanSeeAServiceDetailAsLoggedInUser()
    {
        $user = $this->createUser();

        $servizio = new Servizio();
        $servizio->setName('Secondo servizio');
        $this->em->persist($servizio);
        $this->em->flush();

        $servizioDetailUrl = $this->router->generate('servizi_show', ['slug' => $servizio->getSlug()], Router::ABSOLUTE_URL);

        $crawler = $this->client->request('GET', $this->router->generate('servizi_list'), [], [], ['HTTP_REMOTE_USER' => $user->getName()]);
        $detailLink = $crawler->selectLink('Secondo servizio')->link()->getUri();

        $this->assertEquals($servizioDetailUrl, $detailLink);

        $this->client->request('GET', $detailLink, [], [], ['HTTP_REMOTE_USER' => $user->getName()]);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testICanSeeAServiceApplicationLinkAsLoggedInUser()
    {
        $user = $this->createUser();

        $servizio = new Servizio();
        $servizio->setName('Terzo servizio');
        $this->em->persist($servizio);
        $this->em->flush();

        $servizioDetailUrl = $this->router->generate('servizi_run', ['slug' => $servizio->getSlug()], Router::ABSOLUTE_URL);

        $crawler = $this->client->request('GET', $this->router->generate('servizi_show', ['slug' => $servizio->getSlug()]), [], [], ['HTTP_REMOTE_USER' => $user->getName()]);
        $detailLink = $crawler->selectLink($this->translator->trans('accedi_al_servizio', ['%name%' => $servizio->getName()]))->link()->getUri();
        $this->assertEquals($servizioDetailUrl, $detailLink);

    }
}

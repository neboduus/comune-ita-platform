<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\TerminiUtilizzo;
use AppBundle\Entity\User;
use Monolog\Logger;
use Symfony\Component\VarDumper\VarDumper;
use Tests\AppBundle\Base\AppTestCase;
use Symfony\Component\HttpFoundation\Response;

class DefaultControllerTest extends AppTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->cleanDb(User::class);
        $this->cleanDb(TerminiUtilizzo::class);
    }

    public function testIndex()
    {
        $this->client->request('GET', '/');

        $this->assertContains('Hello World', $this->client->getResponse()->getContent());
    }

    public function testISeeMyName()
    {
        $user = $this->createUser();

        $route = $this->router->generate('servizi_list');
        $this->client->request('GET', $route, [], [], ['HTTP_REMOTE_USER' => $user->getName()]);

        $this->assertContains($user->getName(), $this->client->getResponse()->getContent());
    }

    /**
     * @dataProvider protectedRoutesProvider
     * @param array $route
     */
    public function testIGetAccessDeniedErrorWhenAccessProtectedResourcesAsAnonymousUser($route)
    {
        $this->client->request('GET', $route);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function protectedRoutesProvider()
    {
        return array(
            array('/pratiche/')
        );
    }

    public function testIAmRedirectedToTermAcceptPageWhenIAccessAsLoggedUserForTheFirstTime()
    {
        $user = $this->createUser(false);

        $this->client->request('GET', '/servizi/', [], [], ['HTTP_REMOTE_USER' => $user->getName()]);

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals($this->router->generate('terms_accept'), $response->headers->get('location'));
    }

    public function testIAmNotRedirectedToTermAcceptPageIfTermsAreAccepted()
    {
        $user = $this->createUser();

        $this->client->request('GET', '/', [], [], ['HTTP_REMOTE_USER' => $user->getName()]);

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testICanAcceptTermsConditions()
    {
        $mockLogger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        //@todo testare i log
        //$mockLogger->expects($this->once())->method('info')->with($this->any());

        $termine = new TerminiUtilizzo();
        $termine->setName('Test')->setText('Bla bla bla');
        $this->em->persist($termine);
        $this->em->flush();

        $user = $this->createUser(false);

        $crawler = $this->client->request('GET', $this->router->generate('terms_accept'), [], [], ['HTTP_REMOTE_USER' => $user->getName()]);

        $form = $crawler->selectButton('Salva')
            ->form();

        $this->client->followRedirects();
        $this->client->getContainer()->set('logger', $mockLogger);
        $this->client->submit($form);

        $user = $this->em->getRepository('AppBundle:User')->find($user->getId());

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertTrue($user->getTermsAccepted());


    }

}

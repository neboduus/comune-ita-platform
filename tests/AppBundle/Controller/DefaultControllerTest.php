<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\TerminiUtilizzo;
use AppBundle\Entity\User;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Tests\AppBundle\Base\AppTestCase;

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

        $mockLogger->expects($this->exactly(2))->method('info');

        static::$kernel->setKernelModifier(function ($kernel) use ($mockLogger) {
            $kernel->getContainer()->set('logger', $mockLogger);
        });

        $termine = new TerminiUtilizzo();
        $termine->setName('Test')->setText('Bla bla bla');
        $this->em->persist($termine);
        $this->em->flush();

        $user = $this->createUser(false);

        $crawler = $this->client->request('GET', $this->router->generate('terms_accept'), [], [], ['HTTP_REMOTE_USER' => $user->getName()]);

        $form = $crawler->selectButton($this->container->get('translator')->trans('salva'))
            ->form();

        $this->client->submit($form);

        $this->em->refresh($user);
        $this->assertTrue($user->getTermsAccepted());
        $this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

}

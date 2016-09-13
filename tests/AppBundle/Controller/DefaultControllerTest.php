<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\ComponenteNucleoFamiliare;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\TerminiUtilizzo;
use AppBundle\Entity\User;
use AppBundle\Services\CPSUserProvider;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Tests\AppBundle\Base\AbstractAppTestCase;

class DefaultControllerTest extends AbstractAppTestCase
{

    /**
     * @var CPSUserProvider
     */
    protected $userProvider;

    public function setUp()
    {
        parent::setUp();
        $this->userProvider = $this->container->get('ocsdc.cps.userprovider');
        $this->cleanDb(Allegato::class);
        $this->cleanDb(ComponenteNucleoFamiliare::class);
        $this->cleanDb(Pratica::class);
        $this->cleanDb(User::class);
        $this->cleanDb(TerminiUtilizzo::class);
    }

    public function testIndex()
    {
        $this->client->request('GET', '/');

        $this->assertContains('Stanza del cittadino', $this->client->getResponse()->getContent());
    }

    public function testISeeMyNameAsLoggedInUser()
    {
        $user = $this->createCPSUser();

        $route = $this->router->generate('pratiche');
        $this->clientRequestAsCPSUser($user, 'GET', $route);

        $this->assertContains($user->getFullName(), $this->client->getResponse()->getContent());
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

    public function testIAmRedirectedToTermAcceptPageWhenIAccessForTheFirstTimeAsLoggedInUser()
    {
        $user = $this->createCPSUser(false);

        $this->clientRequestAsCPSUser($user, 'GET', $this->router->generate('pratiche'));

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals($this->router->generate('terms_accept', ['r' => 'pratiche']), $response->headers->get('location'));
    }

    public function testIAmRedirectedToOriginalPageWhenIAcceptTermsForTheFirstTimeAsLoggedInUser()
    {
        $user = $this->createCPSUser(false);

        $this->client->followRedirects();
        $crawler = $this->clientRequestAsCPSUser($user, 'GET', $this->router->generate('pratiche'));
        $form = $crawler->selectButton($this->translator->trans('salva'))->form();
        $this->client->submit($form);
        $this->assertEquals(
            $this->client->getRequest()->getUri(),
            $this->router->generate('pratiche', [], Router::ABSOLUTE_URL)
        );
    }

    /**
     * @test
     */
    public function testOriginalQueryParametersArepreservedWhenIAmRedirectedToOriginalPageAfterAcceptingTermsForTheFirstTimeAsLoggedInUser()
    {
        $user = $this->createCPSUser(false);

        $params = [
            'servizio' => 'someservice',
            'a' => 'b',
            'c' => 'd',
            'e' => 11,
        ];

        $this->client->followRedirects();
        $crawler = $this->clientRequestAsCPSUser($user, 'GET', $this->router->generate('pratiche_new', $params));
        $form = $crawler->selectButton($this->translator->trans('salva'))->form();
        $this->client->submit($form);
        $this->assertEquals(
            $this->client->getRequest()->getUri(),
            $this->router->generate('pratiche_new', $params, Router::ABSOLUTE_URL)
        );
        unset($params['servizio']);
        $this->assertEquals(
            $params,
            $this->client->getRequest()->query->all()
        );
    }

    public function testIAmNotRedirectedToTermAcceptPageIfTermsAreAccepted()
    {
        $user = $this->createCPSUser();

        $this->clientRequestAsCPSUser($user, 'GET', '/');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testICanAcceptTermsConditionsAsLoggedInUser()
    {
        $mockLogger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();

        $mockLogger->expects($this->exactly(2))->method('info');

        static::$kernel->setKernelModifier(function (KernelInterface $kernel) use ($mockLogger) {
            $kernel->getContainer()->set('logger', $mockLogger);
        });

        $termine = new TerminiUtilizzo();
        $termine->setName('Test')->setText('Bla bla bla');
        $this->em->persist($termine);
        $this->em->flush();

        $user = $this->createCPSUser(false);

        $crawler =$this->clientRequestAsCPSUser($user, 'GET', $this->router->generate('terms_accept'));

        $form = $crawler->selectButton($this->translator->trans('salva'))
            ->form();

        $this->client->submit($form);

        $this->em->refresh($user);
        $this->assertTrue($user->getTermsAccepted());
        $this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

}

<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Ente;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use AppBundle\Logging\LogConstants;
use AppBundle\Services\CPSUserProvider;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpKernel\KernelInterface;
use Tests\AppBundle\Base\AbstractAppTestCase;

/**
 * Class PraticaControllerTest
 */
class PraticaControllerTest extends AbstractAppTestCase
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
    public function testAsLoggedUserISeeAllMyPratiche()
    {
        $myUser = $this->createCPSUser(true);
        $this->createPratiche($myUser);

        $otherUser = $this->createCPSUser(true);
        $this->createPratiche($otherUser);

        $repo = $this->em->getRepository("AppBundle:Pratica");
        $myUserPraticheCountAfterInsert = count($repo->findByUser($myUser));

        $otherUserPraticheCountAfterInsert = count($repo->findByUser($otherUser));
        $this->assertGreaterThan(0, $otherUserPraticheCountAfterInsert);

        $crawler = $this->clientRequestAsCPSUser($myUser, 'GET', '/pratiche/');

        $renderedPraticheCount = $crawler->filterXPath('//*[@data-user="'.$myUser->getId().'"]')->count();
        $this->assertEquals($myUserPraticheCountAfterInsert, $renderedPraticheCount);

        $renderedOtherUserPraticheCount = $crawler->filterXPath('//*[@data-user="'.$otherUser->getId().'"]')->count();
        $this->assertEquals(0, $renderedOtherUserPraticheCount);
    }


    /**
     * @test
     */
    public function testAsLoggedUserISeeAllMyPraticheInCorrectOrder()
    {
        $user = $this->createCPSUser(true);
        $this->setupPraticheForUser($user);
        $expectedStatuses = $this->getExpectedPraticaStatuses();

        $crawler = $this->clientRequestAsCPSUser($user, 'GET', '/pratiche/');
        $renderedPraticheCount = $crawler->filterXPath('//*[@data-user="'.$user->getId().'"]')->count();
        $this->assertEquals(count($expectedStatuses), $renderedPraticheCount);

        //For now this logic is enough since sorting is based on actual constants values
        //it's quite brittle though
        rsort($expectedStatuses);
        for ($i = 0; $i < count($expectedStatuses); $i ++) {
            $statusPratica = $crawler->filterXPath('//*[@data-user="'.$user->getId().'"]')->getNode($i)->getAttribute('data-status');
            $this->assertEquals($statusPratica, $expectedStatuses[$i]);
        }
    }

    /**
     * @test
     */
    public function testANewPraticaIsPersistedWhenIStartTheFormApplicationAsLoggedUser()
    {
        $mockLogger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $mockLogger->expects($this->exactly(1))
                   ->method('info')
                   ->with(LogConstants::PRATICA_CREATED);

        $this->container->set('logger', $mockLogger);
        $user = $this->createCPSUser();

        $praticheRepository = $this->em->getRepository('AppBundle:Pratica');
        $tutteLePratiche = count($praticheRepository->findAll());
        $miePratiche = count($praticheRepository->findByUser($user));

        $servizio = new Servizio();
        $servizio->setName('Terzo servizio');
        $this->em->persist($servizio);
        $this->em->flush();

        $this->clientRequestAsCPSUser($user, 'GET', $this->router->generate(
            'pratiche_new',
            ['servizio' => $servizio->getSlug()]
        ));

        $tutteLePraticheNew = count($praticheRepository->findAll());
        $miePraticheNew = count($praticheRepository->findByUser($user));

        $this->assertEquals(++$tutteLePratiche, $tutteLePraticheNew);
        $this->assertEquals(++$miePratiche, $miePraticheNew);
    }

    /**
     * @test
     */
    public function testISeeistruzioniIscrizioneAsiloNidoApplicationFormWhenIStartTheFormAsLoggedUser()
    {
        $mockLogger = $this->getMockLogger();
        $mockLogger->expects($this->exactly(2))
                   ->method('info')
            ->with($this->callback(function ($subject) {
                $expectedArgs = [
                    LogConstants::PRATICA_CREATED,
                    LogConstants::PRATICA_COMPILING_STEP,
                ];

                return in_array($subject, $expectedArgs);
            }));

        static::$kernel->setKernelModifier(function (KernelInterface $kernel) use ($mockLogger) {
            $kernel->getContainer()->set('logger', $mockLogger);
        });
        $user = $this->createCPSUser();

        $ente1 = new Ente();
        $ente1->setName('Ente di prova');
        $this->em->persist($ente1);
        $this->em->flush();

        $ente2 = new Ente();
        $ente2->setName('Ente di prova 2');
        $this->em->persist($ente2);
        $this->em->flush();

        $servizio = new Servizio();
        $servizio->setName('Altro servizio')->setEnti([$ente1, $ente2]);
        $this->em->persist($servizio);
        $this->em->flush();

        $this->client->followRedirects();
        $this->clientRequestAsCPSUser($user, 'GET', $this->router->generate(
            'pratiche_new',
            ['servizio' => $servizio->getSlug()]
        ));

        $this->assertContains('iscrizione_asilo_nido_istruzioni', $this->client->getResponse()->getContent());
    }
}

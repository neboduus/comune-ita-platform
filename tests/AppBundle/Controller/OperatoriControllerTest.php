<?php
namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\ComponenteNucleoFamiliare;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Ente;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\User;
use AppBundle\Logging\LogConstants;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Tests\AppBundle\Base\AbstractAppTestCase;

/**
 * Class OperatoriControllerTest
 */
class OperatoriControllerTest extends AbstractAppTestCase
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        $this->cleanDb(ComponenteNucleoFamiliare::class);
        $this->cleanDb(Allegato::class);
        $this->cleanDb(Pratica::class);
        $this->cleanDb(OperatoreUser::class);
        $this->cleanDb(CPSUser::class);
    }

    /**
     * @test
     */
    public function testICannotAccessOperatoriHomePageAsAnonymousUser()
    {
        $operatoriHome = $this->router->generate('operatori_index');
        $this->client->request('GET', $operatoriHome);
        $this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function testICanAccessOperatoriHomePageAsLoggedInOperatore()
    {
        $password = 'pa$$word';
        $username = 'username';

        $user = $this->createOperatoreUser($username, $password);

        $operatoriHome = $this->router->generate('operatori_index');
        $this->client->request('GET', $operatoriHome, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doTestISeeMyNameAsLoggedInUser($user, $this->client->getResponse());
    }

    /**
     * @test
     */
    public function testICanSeeMyPraticheWhenAccessingOperatoriHomePageAsLoggedInOperatore()
    {
        $password = 'pa$$word';
        $username = 'username';

        $operatore = $this->createOperatoreUser($username, $password);
        $altroOperatore = $this->createOperatoreUser($username.'2', $password);
        $user = $this->createCPSUser();

        $praticaSubmitted = $this->setupPraticheForUserWithOperatoreAndStatus($user, $operatore, Pratica::STATUS_SUBMITTED);
        $praticaRegistered = $this->setupPraticheForUserWithOperatoreAndStatus($user, $operatore, Pratica::STATUS_REGISTERED);
        $praticaPending = $this->setupPraticheForUserWithOperatoreAndStatus($user, $operatore, Pratica::STATUS_PENDING);
        $praticaSubmittedMaAltroOperatore = $this->setupPraticheForUserWithOperatoreAndStatus($user, $altroOperatore, Pratica::STATUS_SUBMITTED);

        $operatoriHome = $this->router->generate('operatori_index');
        $crawler = $this->client->request('GET', $operatoriHome, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->doTestISeeMyNameAsLoggedInUser($operatore, $this->client->getResponse());

        $praticheCount = $crawler->filter('.list.mie')->filter('.pratica')->count();
        $this->assertEquals(3, $praticheCount);

        $expectedPratiche = [
            $praticaSubmitted,
            $praticaRegistered,
            $praticaPending,
        ];

        $unexpectedPratiche = [
            $praticaSubmittedMaAltroOperatore,
        ];

        foreach ($expectedPratiche as $pratica) {
            $this->assertEquals(1, $crawler->filterXPath('//*[@data-pratica="'.$pratica->getId().'"]')->count());
        }

        foreach ($unexpectedPratiche as $pratica) {
            $this->assertEquals(0, $crawler->filterXPath('//*[@data-pratica="'.$pratica->getId().'"]')->count());
        }
    }

    /**
     * @test
     */
    public function testICanAssignFascicoloNumberToPratiche()
    {
        $password = 'pa$$word';
        $username = 'username';
        $numeroDiFascicolo = 'NumeroDiFascicolo'.md5(time());

        $operatore = $this->createOperatoreUser($username, $password);
        $user = $this->createCPSUser();

        $pratica = $this->setupPraticheForUserWithOperatoreAndStatus($user, $operatore, Pratica::STATUS_PENDING);

        $this->assertNull($pratica->getNumeroFascicolo());
        $this->assertEquals(0, $pratica->getNumeriProtocollo()->count());

        $mockLogger = $this->getMockLogger();
        $mockLogger->expects($this->once())
            ->method('info')
            ->with(sprintf(LogConstants::PRATICA_FASCICOLO_ASSEGNATO, $pratica->getId(), $numeroDiFascicolo));

        static::$kernel->setKernelModifier(function (KernelInterface $kernel) use ($mockLogger) {
            $kernel->getContainer()->set('logger', $mockLogger);
        });

        $editPraticaUrl = $this->router->generate('operatori_set_numero_fascicolo_a_pratica', ['pratica' => $pratica->getId()]);
        $crawler = $this->client->request('GET', $editPraticaUrl, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $form = $crawler->selectButton($this->translator->trans('salva'))->form();
        $values = $form->getValues();

        $values['numero_fascicolo_pratica[numeroFascicolo]'] = $numeroDiFascicolo;

        $form->setValues($values);
        $this->client->submit($form);
        $this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        $pratica = $this->em->getRepository('AppBundle:Pratica')->find($pratica->getId());
        $this->assertEquals($numeroDiFascicolo, $pratica->getNumeroFascicolo());
    }

    /**
     * @test
     */
    public function testICanAssignProtocolloNumberToPratiche()
    {
        $password = 'pa$$word';
        $username = 'username';
        $numeroDiProtocollo = 'NumeroDiProtocollo'.md5(time());

        $operatore = $this->createOperatoreUser($username, $password);
        $user = $this->createCPSUser();

        $pratica = $this->setupPraticheForUserWithOperatoreAndStatus($user, $operatore, Pratica::STATUS_PENDING);

        $this->assertEquals(0, $pratica->getNumeriProtocollo()->count());

        $mockLogger = $this->getMockLogger();
        $mockLogger->expects($this->once())
            ->method('info')
            ->with(sprintf(LogConstants::PRATICA_PROTOCOLLO_ASSEGNATO, $pratica->getId(), $numeroDiProtocollo));

        static::$kernel->setKernelModifier(function (KernelInterface $kernel) use ($mockLogger) {
            $kernel->getContainer()->set('logger', $mockLogger);
        });

        $editPraticaUrl = $this->router->generate('operatori_set_numero_protocollo_a_pratica', ['pratica' => $pratica->getId()]);
        $crawler = $this->client->request('GET', $editPraticaUrl, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $form = $crawler->selectButton($this->translator->trans('salva'))->form();
        $values = $form->getValues();
        $values['numero_protocollo_pratica[numeroProtocollo]'] = $numeroDiProtocollo;

        $form->setValues($values);
        $this->client->submit($form);
        $this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());

        $pratica = $this->em->getRepository('AppBundle:Pratica')->find($pratica->getId());
        $this->assertEquals($numeroDiProtocollo, $pratica->getNumeroProtocollo());
    }

    /**
     * @test
     */
    public function testICanSeeUnassignedPraticheForMyEnteWhenAccessingOperatoriHomePageAsLoggedInOperatore()
    {
        $password = 'pa$$word';
        $username = 'username';

        $enti = $this->createEnti();
        $ente1 = $enti[0];
        $ente2 = $enti[1];

        $this->createOperatoreUser($username, $password, $ente1);
        $user = $this->createCPSUser();

        $praticaSubmitted = $this->setupPraticheForUserWithEnteAndStatus($user, $ente1, Pratica::STATUS_SUBMITTED);
        $praticaRegistered = $this->setupPraticheForUserWithEnteAndStatus($user, $ente1, Pratica::STATUS_REGISTERED);
        $praticaPending = $this->setupPraticheForUserWithEnteAndStatus($user, $ente1, Pratica::STATUS_PENDING);
        $praticaPendingMaAltroEnte = $this->setupPraticheForUserWithEnteAndStatus($user, $ente2, Pratica::STATUS_PENDING);

        $operatoriHome = $this->router->generate('operatori_index');
        $crawler = $this->client->request('GET', $operatoriHome, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $praticheCount = $crawler->filter('.list.libere')->filter('.pratica')->count();
        $this->assertEquals(3, $praticheCount);

        $expectedPratiche = [
            $praticaSubmitted,
            $praticaRegistered,
            $praticaPending,
        ];

        $unexpectedPratiche = [
            $praticaPendingMaAltroEnte,
        ];

        foreach ($expectedPratiche as $pratica) {
            $this->assertEquals(1, $crawler->filterXPath('//*[@data-pratica="'.$pratica->getId().'"]')->count());
        }

        foreach ($unexpectedPratiche as $pratica) {
            $this->assertEquals(0, $crawler->filterXPath('//*[@data-pratica="'.$pratica->getId().'"]')->count());
        }
    }

    /**
     * @test
     */
    public function testICanSeeMyCompletedPraticheWhenAccessingOperatoriHomePageAsLoggedInOperatore()
    {
        $password = 'pa$$word';
        $username = 'username';

        $operatore = $this->createOperatoreUser($username, $password);
        $altroOperatore = $this->createOperatoreUser($username.'2', $password);
        $user = $this->createCPSUser();

        $praticaSubmitted = $this->setupPraticheForUserWithOperatoreAndStatus($user, $operatore, Pratica::STATUS_SUBMITTED);
        $praticaRegistered = $this->setupPraticheForUserWithOperatoreAndStatus($user, $operatore, Pratica::STATUS_REGISTERED);
        $praticaPending = $this->setupPraticheForUserWithOperatoreAndStatus($user, $operatore, Pratica::STATUS_PENDING);
        $praticaComplete = $this->setupPraticheForUserWithOperatoreAndStatus($user, $operatore, Pratica::STATUS_COMPLETE);

        $praticaCompletedMaAltroOperatore = $this->setupPraticheForUserWithOperatoreAndStatus($user, $altroOperatore, Pratica::STATUS_COMPLETE);

        $operatoriHome = $this->router->generate('operatori_index');
        $crawler = $this->client->request('GET', $operatoriHome, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $praticheCount = $crawler->filter('.list.mie')->filter('.pratica')->count();
        $this->assertEquals(3, $praticheCount);

        $expectedPratiche = [
            $praticaSubmitted,
            $praticaRegistered,
            $praticaPending,
            $praticaComplete,
        ];

        $unexpectedPratiche = [
            $praticaCompletedMaAltroOperatore,
        ];

        foreach ($expectedPratiche as $pratica) {
            $this->assertEquals(1, $crawler->filterXPath('//*[@data-pratica="'.$pratica->getId().'"]')->count());
        }

        foreach ($unexpectedPratiche as $pratica) {
            $this->assertEquals(0, $crawler->filterXPath('//*[@data-pratica="'.$pratica->getId().'"]')->count());
        }
    }

    /**
     * @test
     */
    public function testICanAccessToMyAssignedPraticaDetail()
    {
        $password = 'pa$$word';
        $username = 'username';

        $operatore = $this->createOperatoreUser($username, $password);
        $altroOperatore = $this->createOperatoreUser($username.'2', $password);
        $user = $this->createCPSUser();

        $pratica = $this->setupPraticheForUserWithOperatoreAndStatus($user, $operatore, Pratica::STATUS_PENDING);
        $detailPraticaUrl = $this->router->generate('operatori_show_pratica', ['pratica' => $pratica->getId()]);

        $operatoriHome = $this->router->generate('operatori_index');
        $crawler = $this->client->request('GET', $operatoriHome, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertContains($detailPraticaUrl, $this->client->getResponse()->getContent());

        $this->client->request('GET', $detailPraticaUrl, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

    }

    /**
     * @test
     */
    public function testICannotAccessToUnassignedPraticaDetail()
    {
        $password = 'pa$$word';
        $username = 'username';

        $enti = $this->createEnti();
        $ente1 = $enti[0];

        $operatore = $this->createOperatoreUser($username, $password, $ente1);
        $user = $this->createCPSUser();

        $pratica = $this->setupPraticheForUserWithEnteAndStatus($user, $ente1, Pratica::STATUS_SUBMITTED);
        $detailPraticaUrl = $this->router->generate('operatori_show_pratica', ['pratica' => $pratica->getId()]);

        $operatoriHome = $this->router->generate('operatori_index');
        $crawler = $this->client->request('GET', $operatoriHome, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertNotContains($detailPraticaUrl, $this->client->getResponse()->getContent());

        $this->client->request('GET', $detailPraticaUrl, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function testICanAssignToMyselfAUnassignedPratica()
    {
        $password = 'pa$$word';
        $username = 'username';

        $enti = $this->createEnti();
        $ente1 = $enti[0];

        $operatore = $this->createOperatoreUser($username, $password, $ente1);
        $user = $this->createCPSUser();
        $pratica = $this->setupPraticheForUserWithEnteAndStatus($user, $ente1, Pratica::STATUS_SUBMITTED);
        $pratica->setNumeroProtocollo('test');
        $pratica->setNumeroFascicolo('test');
        $this->em->flush($pratica);

        $mockLogger = $this->getMockLogger();
        $mockLogger->expects($this->once())
                   ->method('info')
                   ->with(LogConstants::PRATICA_ASSIGNED);

        $mockMailer = $this->setupSwiftmailerMock([$user, $operatore]);

        static::$kernel->setKernelModifier(function (KernelInterface $kernel) use ($mockLogger, $mockMailer) {
            $kernel->getContainer()->set('logger', $mockLogger);
            $kernel->getContainer()->set('swiftmailer.mailer.default', $mockMailer);
        });

        $autoassignPraticaUrl = $this->router->generate('operatori_autoassing_pratica', ['pratica' => $pratica->getId()]);

        $this->client->followRedirects();
        $this->client->request('GET', $autoassignPraticaUrl, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $pratica = $this->em->getRepository('AppBundle:Pratica')->find($pratica->getId());
        $this->assertEquals(Pratica::STATUS_PENDING, $pratica->getStatus());
    }

    /**
     * @test
     */
    public function testICanNotAssignToMyselfAUnassignedPraticaWithoutProtocollo()
    {
        $password = 'pa$$word';
        $username = 'username';

        $enti = $this->createEnti();
        $ente1 = $enti[0];

        $operatore = $this->createOperatoreUser($username, $password, $ente1);
        $user = $this->createCPSUser();
        $pratica = $this->setupPraticheForUserWithEnteAndStatus($user, $ente1, Pratica::STATUS_SUBMITTED);

        $autoassignPraticaUrl = $this->router->generate('operatori_autoassing_pratica', ['pratica' => $pratica->getId()]);

        $this->client->followRedirects();
        $this->client->request('GET', $autoassignPraticaUrl, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function testICannotAssignToMyselfAnAssignedPratica()
    {
        $password = 'pa$$word';
        $username = 'username';

        $enti = $this->createEnti();
        $ente1 = $enti[0];

        $operatore = $this->createOperatoreUser($username, $password, $ente1);
        $user = $this->createCPSUser();

        $pratica = $this->setupPraticheForUserWithOperatoreAndStatus($user, $operatore, Pratica::STATUS_PENDING);
        $autoassignPraticaUrl = $this->router->generate('operatori_autoassing_pratica', ['pratica' => $pratica->getId()]);

        $this->client->followRedirects();
        $this->client->request('GET', $autoassignPraticaUrl, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function testICanCommentMyPratica()
    {
        $password = 'pa$$word';
        $username = 'username';

        $operatore = $this->createOperatoreUser($username, $password);
        $user = $this->createCPSUser();
        $pratica = $this->setupPraticheForUserWithOperatoreAndStatus($user, $operatore, Pratica::STATUS_PENDING);

        $mockLogger = $this->getMockLogger();
        $mockLogger->expects($this->once())
                   ->method('info')
                   ->with(LogConstants::PRATICA_COMMENTED);

        static::$kernel->setKernelModifier(function (KernelInterface $kernel) use ($mockLogger) {
            $kernel->getContainer()->set('logger', $mockLogger);
        });

        $detailPraticaUrl = $this->router->generate('operatori_show_pratica', ['pratica' => $pratica->getId()]);

        $crawler = $this->client->request('GET', $detailPraticaUrl, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $text = 'ma quante belle figlie madamadorè';
        $form = $crawler->selectButton($this->translator->trans('operatori.aggiungi_commento'))->form();
        $crawler = $this->client->submit($form);

        $this->assertEquals(302, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");

        $pratica = $this->em->getRepository('AppBundle:Pratica')->find($pratica->getId());
        foreach($pratica->getCommenti() as $commento){
            $this->assertEquals($commento['text'], $text);
        }
    }

    /**
     * @test
     */
    public function testICanApproveMyPratica()
    {
        $password = 'pa$$word';
        $username = 'username';

        $operatore = $this->createOperatoreUser($username, $password);
        $user = $this->createCPSUser();
        $pratica = $this->setupPraticheForUserWithOperatoreAndStatus($user, $operatore, Pratica::STATUS_PENDING);

        $mockLogger = $this->getMockLogger();
        $mockLogger->expects($this->once())
                   ->method('info')
                   ->with(LogConstants::PRATICA_APPROVED);

        $mockMailer = $this->setupSwiftmailerMock([$user, $operatore]);

        static::$kernel->setKernelModifier(function (KernelInterface $kernel) use ($mockLogger, $mockMailer) {
            $kernel->getContainer()->set('logger', $mockLogger);
            $kernel->getContainer()->set('swiftmailer.mailer.default', $mockMailer);
        });

        $detailPraticaUrl = $this->router->generate('operatori_show_pratica', ['pratica' => $pratica->getId()]);
        $approvePraticaUrl = $this->router->generate('operatori_approva_pratica', ['pratica' => $pratica->getId()]);

        $this->client->request('GET', $detailPraticaUrl, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));

        $this->assertContains($approvePraticaUrl, $this->client->getResponse()->getContent());

        $this->client->request('GET', $approvePraticaUrl, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");

        $pratica = $this->em->getRepository('AppBundle:Pratica')->find($pratica->getId());
        $this->assertEquals($pratica->getStatus(), Pratica::STATUS_COMPLETE);
    }

    /**
     * @test
     */
    public function testICanDenyMyPratica()
    {
        $password = 'pa$$word';
        $username = 'username';

        $operatore = $this->createOperatoreUser($username, $password);
        $user = $this->createCPSUser();
        $pratica = $this->setupPraticheForUserWithOperatoreAndStatus($user, $operatore, Pratica::STATUS_PENDING);

        $mockLogger = $this->getMockLogger();
        $mockLogger->expects($this->once())
                   ->method('info')
                   ->with(LogConstants::PRATICA_CANCELLED);

        $mockMailer = $this->setupSwiftmailerMock([$user, $operatore]);

        static::$kernel->setKernelModifier(function (KernelInterface $kernel) use ($mockLogger, $mockMailer) {
            $kernel->getContainer()->set('logger', $mockLogger);
            $kernel->getContainer()->set('swiftmailer.mailer.default', $mockMailer);
        });

        $detailPraticaUrl = $this->router->generate('operatori_show_pratica', ['pratica' => $pratica->getId()]);
        $denyPraticaUrl = $this->router->generate('operatori_rifiuta_pratica', ['pratica' => $pratica->getId()]);

        $this->client->request('GET', $detailPraticaUrl, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertContains($denyPraticaUrl, $this->client->getResponse()->getContent());

        $this->client->request('GET', $denyPraticaUrl, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");

        $pratica = $this->em->getRepository('AppBundle:Pratica')->find($pratica->getId());
        $this->assertEquals($pratica->getStatus(), Pratica::STATUS_CANCELLED);
    }


    /**
     * @test
     */
    public function testICannotAccessOperatoriListAsNormalOperatore()
    {
        $password = 'pa$$word';
        $username = 'username';

        $this->createOperatoreUser($username, $password);

        $operatoriList = $this->router->generate('operatori_list_by_ente');
        $this->client->request('GET', $operatoriList);
        $this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
        $this->assertRegExp('/\/login$/', $this->client->getResponse()->headers->get('location'));
    }

    /**
     * @test
     */
    public function testICanAccessOperatoriListAsAdminOperatore()
    {
        $password = 'pa$$word';
        $username = 'username';

        $operatore = $this->createOperatoreUser($username, $password);
        $operatore->addRole(User::ROLE_OPERATORE_ADMIN);

        $operatoriList = $this->router->generate('operatori_list_by_ente');
        $this->client->request('GET', $operatoriList, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function testICanSeeOnlyOperatoriOfMyEnte()
    {
        /** @var Ente[] $enti */
        $enti = $this->createEnti();

        $password = 'operatore_admin';
        $username = 'operatore_admin';
        $operatoreAdmin = $this->createOperatoreUser($username, $password, $enti[0]);
        $operatoreAdmin->addRole(User::ROLE_OPERATORE_ADMIN);

        $sameEnteOperatore = $this->createOperatoreUser('same_ente', 'same_ente', $enti[0]);

        $otherEnteOperatore = $this->createOperatoreUser('other_ente', 'other_ente', $enti[1]);

        $operatoriList = $this->router->generate('operatori_list_by_ente');
        $crawler = $this->client->request('GET', $operatoriList, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));

        $operatoriCount = $crawler->filter('.operatore')->count();
        $this->assertEquals(2, $operatoriCount);

        $this->assertEquals(0, $crawler->filterXPath('//*[@data-opertore="'.$otherEnteOperatore->getId().'"]')->count());

    }

    /**
     * @test
     */
    public function testICanEditAmbitoAsAdminOperatore()
    {
        /** @var Ente[] $enti */
        $enti = $this->createEnti();

        $password = 'operatore_admin';
        $username = 'operatore_admin';
        $operatoreAdmin = $this->createOperatoreUser($username, $password, $enti[0]);
        $operatoreAdmin->addRole(User::ROLE_OPERATORE_ADMIN);

        $sameEnteOperatore = $this->createOperatoreUser('same_ente', 'same_ente', $enti[0]);

        $mockLogger = $this->getMockLogger();
        $mockLogger->expects($this->once())
            ->method('info')
            ->with(LogConstants::OPERATORE_ADMIN_HAS_CHANGED_OPERATORE_AMBITO);

        static::$kernel->setKernelModifier(function (KernelInterface $kernel) use ($mockLogger) {
            $kernel->getContainer()->set('logger', $mockLogger);
        });

        $detailOperatoreUrl = $this->router->generate('operatori_detail', ['operatore' => $sameEnteOperatore->getId()]);

        $crawler = $this->client->request('GET', $detailOperatoreUrl, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $text = time();
        $form = $crawler->selectButton($this->translator->trans('operatori.profile.salva_modifiche'))->form([
            'form[ambito]' => $text
        ]);;
        $this->client->submit($form);

        $this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");

        $sameEnteOperatore = $this->em->getRepository('AppBundle:OperatoreUser')->find($sameEnteOperatore->getId());
        $this->assertEquals($sameEnteOperatore->getAmbito(), $text);

    }

    /**
     * @test
     */
    public function testICanDoLogoutAsOperatore()
    {
        $password = 'pa$$word';
        $username = 'username';

        $operatore = $this->createOperatoreUser($username, $password);

        $operatoriHome = $this->router->generate('operatori_index');
        $crawler = $this->client->request('GET', $operatoriHome, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $logout = $crawler->selectLink($this->translator->trans('logout'))->link();
        $this->client->click($logout);
        $this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
        $this->assertRegExp('/\/operatori\/$/', $this->client->getResponse()->headers->get('location'));

        $this->client->request('GET', $operatoriHome);
        $this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
        $this->assertRegExp('/\/login$/', $this->client->getResponse()->headers->get('location'));
    }


}

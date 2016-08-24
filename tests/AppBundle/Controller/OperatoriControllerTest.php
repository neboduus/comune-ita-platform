<?php
namespace Tests\AppBundle\Controller;

use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Ente;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
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

        $this->createOperatoreUser($username, $password);

        $operatoriHome = $this->router->generate('operatori_index');
        $this->client->request('GET', $operatoriHome, array(), array(), array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW' => $password,
        ));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
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
        $user = $this->createCPSUser(true);

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
        $user = $this->createCPSUser(true);

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
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

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
        $user = $this->createCPSUser(true);

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
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

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
        $user = $this->createCPSUser(true);

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
}

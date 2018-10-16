<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Controller\APIController;
use AppBundle\Entity\Allegato;
use AppBundle\Entity\ComponenteNucleoFamiliare;
use AppBundle\Entity\DematerializedFormPratica;
use AppBundle\Entity\Ente;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use AppBundle\Logging\LogConstants;
use AppBundle\Mapper\Giscom\GiscomStatusMapper;
use AppBundle\Mapper\Giscom\SciaPraticaEdilizia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\VarDumper;
use Tests\AppBundle\Base\AbstractAppTestCase;

/**
 * Class ApiControllerTest
 */
class GiscomApiControllerTest extends AbstractAppTestCase
{
    private $GISCOM_PASS;
    const GISCOM_USER = 'giscom';

    /**
     * @var GiscomStatusMapper
     */
    private $giscomStatusMapper;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        $this->GISCOM_PASS = $this->container->getParameter('giscom_password');
        $this->em->getConnection()->executeQuery('DELETE FROM servizio_erogatori')->execute();
        $this->em->getConnection()->executeQuery('DELETE FROM erogatore_ente')->execute();
        $this->em->getConnection()->executeQuery('DELETE FROM ente_asili')->execute();
        $this->cleanDb(ComponenteNucleoFamiliare::class);
        $this->cleanDb(Pratica::class);
        $this->cleanDb(Allegato::class);
        $this->cleanDb(User::class);
        $this->cleanDb(Ente::class);
        $this->cleanDb(Servizio::class);

        $this->giscomStatusMapper = $this->container->get('ocsdc.status_mapper.giscom');
    }

    /**
     * @test
     */
    public function testPraticaStatusCanBeUpdatedByGiscomViaProtectedAPI()
    {
        $this->setupMockedLogger([
            LogConstants::PRATICA_CHANGED_STATUS,
            LogConstants::PRATICA_UPDATED_STATUS_FROM_GISCOM,
        ]);
        $user = $user = $this->createCPSUser();
        $pratica = $this->createPratica($user,null,Pratica::STATUS_REGISTERED);
        $initialStatusCount = $pratica->getStoricoStati()->count();

        $rawStatusChange = [
            'evento' => GiscomStatusMapper::GISCOM_STATUS_PREISTRUTTORIA,
            'time' => '2017-08-25 12:13:33.033',
            'responsabile' => 'Contessa Serbelloni Mazzanti Viendalmare',
            'operatore' => 'giscom',
            'struttura' => 'Edilizia',
        ];

        $expectedTimestamp = (new \DateTime($rawStatusChange['time'], new \DateTimeZone('Europe/Rome')))->getTimestamp();
        $expectedStatusChange = [
            'evento' => $this->giscomStatusMapper->map($rawStatusChange['evento']),
            'timestamp' => (new \DateTime('2017-08-25 12:13:33.033', new \DateTimeZone('Europe/Rome')))->getTimestamp(),
            'responsabile' => 'Contessa Serbelloni Mazzanti Viendalmare',
            'operatore' => 'giscom',
            'struttura' => 'Edilizia',
        ];
        $this->client->request(
            'POST',
            $this->formatPraticaStatusUpdateRoute($pratica),
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => self::GISCOM_USER,
                'PHP_AUTH_PW' => $this->GISCOM_PASS,
            ),
            json_encode(
                $rawStatusChange
            )
        );

        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->em->refresh($pratica);
        $finalStatusCount = $pratica->getStoricoStati()->count();
        $this->assertEquals($initialStatusCount+1, $finalStatusCount);
        $newStatusTimestamp = $pratica->getLatestTimestampForStatus($this->giscomStatusMapper->map($rawStatusChange['evento']));
        $this->assertEquals($expectedTimestamp, $newStatusTimestamp);
        $statoCambiato = $pratica->getStoricoStati()[$newStatusTimestamp];
        $this->assertContains([$expectedStatusChange['evento'], $expectedStatusChange], $statoCambiato);
    }

    /**
     * @test
     */
    public function testPraticaProtocolliCanBeUpdatedByGiscomViaProtectedAPI()
    {
        $this->setupMockedLogger([
            LogConstants::PRATICA_UPDATED_PROTOCOLLO_FROM_GISCOM,
        ]);
        $pratica = $this->setupPraticaScia([]);


        $this->client->request(
            'POST',
            $this->formatPraticaProtocolliUpdateRoute($pratica),
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => self::GISCOM_USER,
                'PHP_AUTH_PW' => $this->GISCOM_PASS,
            ),
            json_encode(
                ['protocolloA', 'protocolloB']
            )
        );

        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->em->refresh($pratica);
        $finalStatusCount = $pratica->getNumeriProtocollo()->count();
        $this->assertEquals(2, $finalStatusCount);
    }

    /**
     * FIXME: missing error checks on protocollo API routes
     */

    /**
     * @test
     */
    public function testPraticaStatusThrowsIfMissingBody()
    {
        $this->setupMockedLogger([
            LogConstants::PRATICA_ERROR_IN_UPDATED_STATUS_FROM_GISCOM,
        ], 'error');
        $user = $user = $this->createCPSUser();
        $pratica = $this->createPratica($user);

        $this->client->request(
            'POST',
            $this->formatPraticaStatusUpdateRoute($pratica),
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => self::GISCOM_USER,
                'PHP_AUTH_PW' => $this->GISCOM_PASS,
            ),
            null
        );
        $this->assertEquals('', $this->client->getResponse()->getContent());
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function testPraticaStatusThrowsIfMissingMandatoryFields()
    {
        $this->setupMockedLogger([
            LogConstants::PRATICA_ERROR_IN_UPDATED_STATUS_FROM_GISCOM,
        ], 'error');
        $user = $user = $this->createCPSUser();
        $pratica = $this->createPratica($user);

        //missing operatore
        $rawStatusChange = [
            'evento' => Pratica::STATUS_SUBMITTED,
            'timestamp' => 123,
            'responsabile' => 'Contessa Serbelloni Mazzanti Viendalmare',
            //'operatore' => 'pippo',
            'struttura' => 'Anagrafe',
        ];

        $this->client->request(
            'POST',
            $this->formatPraticaStatusUpdateRoute($pratica),
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => self::GISCOM_USER,
                'PHP_AUTH_PW' => $this->GISCOM_PASS,
            ),
            json_encode($rawStatusChange)
        );
        $this->assertEquals('', $this->client->getResponse()->getContent());
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function testPraticaStatusAPIIsProtected()
    {
        $user = $user = $this->createCPSUser();
        $pratica = $this->createPratica($user);

        $client = static::createClient();
        $client->restart();
        $client->request(
            'POST',
            $this->formatPraticaStatusUpdateRoute($pratica)
        );

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function testPraticaStatusAPIIsProtectedWithRoleChecking()
    {
        $user = $user = $this->createCPSUser();
        $pratica = $this->createPratica($user);

        $client = static::createClient();
        $client->restart();
        $client->request(
            'POST',
            $this->formatPraticaStatusUpdateRoute($pratica),
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => 'gpa_no_role',
                'PHP_AUTH_PW' => 'gpapass',
            )
        );

        $this->assertEquals(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
    }

    /**
     * @param Pratica $pratica
     * @return string
     */
    private function formatPraticaStatusUpdateRoute(Pratica $pratica):string
    {
        $route = '/api/'.APIController::CURRENT_API_VERSION.'/giscom/pratica/'.$pratica->getId().'/status';

        return $route;
    }

    /**
     * @param Pratica $pratica
     * @return string
     */
    private function formatPraticaProtocolliUpdateRoute(Pratica $pratica):string
    {
        $route = '/api/'.APIController::CURRENT_API_VERSION.'/giscom/pratica/'.$pratica->getId().'/protocolli';

        return $route;
    }

    /**
     * @param Pratica $pratica
     * @return string
     */
    private function formatRichiestaIntegrazioneRoute(Pratica $pratica):string
    {
        $route = '/api/'.APIController::CURRENT_API_VERSION.'/giscom/pratica/'.$pratica->getId().'/richiestaIntegrazioni';

        return $route;
    }
}
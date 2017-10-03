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

    public function testRichiestaIntegrazioneApiClonesLatestActivePraticaForIntegrazione(){
        //stessa cosa del test qui sotto, ma verificando che se viene chiamata una pratica già clonata finiamo per clonare l'unltima in catena  (la più recente) e non la prima
        //c'è il repository che ha un metodo apposito
    }

    public function testRichiestaIntegrazioneApiClonesPraticaForIntegrazione()
    {
        $this->markTestSkipped("Tolto clone");
        $this->setupMockedLogger([
            LogConstants::RICHIESTA_INTEGRAZIONE_FROM_GISCOM,
            LogConstants::PRATICA_CHANGED_STATUS,
            LogConstants::PRATICA_CHANGED_STATUS,
        ]);
        $pratica = $this->setupPraticaScia([]);
        $pratica->setStatus(Pratica::STATUS_REGISTERED);
        $this->em->persist($pratica);
        $this->em->flush();
        sleep(1);
        $emptyMapping = new SciaPraticaEdilizia();
        $emptyMapping = $emptyMapping->toHash();

        unset($emptyMapping['tipo']);
        unset($emptyMapping['moduloDomanda']);
        unset($emptyMapping['elencoAllegatiAllaDomanda']);
        unset($emptyMapping['elencoUlterioriAllegatiTecnici']);
        unset($emptyMapping['elencoProvvedimenti']);

        $this->client->request(
            'POST',
            $this->formatRichiestaIntegrazioneRoute($pratica),
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => self::GISCOM_USER,
                'PHP_AUTH_PW' => $this->GISCOM_PASS,
            ),
            json_encode($emptyMapping)
        );


        /**
         * crea la pratica status pending (o stato giscom)
         * creiamo la richiesta di integrazione lato giscom
         * la mandiamo alle api
         * controlliamo che sia stata creata una pratica nuova,
         * con lo status DRAFT_FOR_INTEGRAZIONE
         * con il dematerializedForm che deve corrispondere all'array_merge dell'originale più la richiesta
         * con il campo originalRequest popolato con la richiesta originale
         * con lo stesso storico stati, più lo stato richiesta integrazione
         *
         * controlliamo che la pratica vecchia sia stata chiusa
         * con lo status REPLACED_FOR_INTEGRAZIONE
         * con il campo replacedById
         *
         */

        /**
         * modifichiamo la pratica per avere sostituita dalla
        stato bozza per integrazione
        stato chiusa per integrazione

        tabella note con id pratica e campo libero

        quando creano via api la richiesta integriamo il dematerialized con la richiesta lato loro

        lato vue se vediamo che c'è la richiesta rdi integrazione mettiamo i campi che servono

        dobbiamo parlare con giscom tramite un id specifico
        quindi teniamo come buono l'id originario
         */
        VarDumper::dump($this->client->getResponse());
        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

        $this->em->refresh($pratica);

        $replacingPratica = $this->em->getRepository('AppBundle:Pratica')->find($pratica->getReplacedBy());

        $this->assertEquals($this->giscomStatusMapper->map(GiscomStatusMapper::GISCOM_STATUS_RICHIESTA_INTEGRAZIONI), $pratica->getStatus());

        $this->assertEquals(\AppBundle\Entity\SciaPraticaEdilizia::STATUS_DRAFT_FOR_INTEGRATION, $replacingPratica->getStatus());

        /**
         * Il test è pressoché istantaneo, quindi i cambi di stato sono tutti sotto la stessa chiave
         * cambiDiStato = [
         *   timestamp -> [ cambiodistato, cambiodistato ... ]
         * ]
         */

        $statiVecchiaPratica = $pratica->getStoricoStati();
        $statiNuovaPratica = $replacingPratica->getStoricoStati();

        $this->assertEquals(count($statiVecchiaPratica->last()) + 1, count($statiNuovaPratica->last()));

        $this->assertArraySubset($pratica->getDematerializedForms(), $replacingPratica->getDematerializedForms());
        $this->assertArraySubset($emptyMapping, $replacingPratica->getDematerializedForms());

        $this->assertEquals($emptyMapping, $replacingPratica->getOriginalIntegrationRequest());

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
        ]);
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
        ]);
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

    public function testPraticaIntegrazioneAPICreatesAnIntegrazioneObject()
    {

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
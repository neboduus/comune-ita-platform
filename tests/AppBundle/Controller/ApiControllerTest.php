<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Controller\APIController;
use AppBundle\Entity\Ente;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Logging\LogConstants;
use Symfony\Component\HttpFoundation\Response;
use Tests\AppBundle\Base\AbstractAppTestCase;

/**
 * Class ApiControllerTest
 */
class ApiControllerTest extends AbstractAppTestCase
{

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        $this->em->getConnection()->executeQuery('DELETE FROM servizio_enti')->execute();
        $this->em->getConnection()->executeQuery('DELETE FROM ente_asili')->execute();
        $this->cleanDb(Pratica::class);
        $this->cleanDb(Ente::class);
        $this->cleanDb(Servizio::class);
    }

    /**
     * @test
     */
    public function testStatusAPI()
    {
        $expectedResponse = (object) [
            'status' => 'ok',
            'version' => APIController::CURRENT_API_VERSION,
        ];
        $this->client->request('GET', '/api/'.APIController::CURRENT_API_VERSION.'/status');

        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertEquals($expectedResponse, $response);
        $this->assertEquals('application/json', $this->client->getResponse()->headers->get('Content-Type'));
    }

    /**
     * @test
     */
    public function testGetServizi()
    {
        $servizio1 = $this->createServizioWithAssociatedEnti([]);
        $servizio2 = $this->createServizioWithAssociatedEnti([]);

        $expectedResponse = [
            (object) [
                'name' => $servizio1->getName(),
                'slug' => $servizio1->getSlug(),
            ],
            (object) [
                'name' => $servizio2->getName(),
                'slug' => $servizio2->getSlug(),
            ],
        ];

        $this->client->request('GET', '/api/'.APIController::CURRENT_API_VERSION.'/services');
        $response = json_decode($this->client->getResponse()->getContent(), false);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * @test
     */
    public function testPraticaStatusCanBeUpdatedViaProtectedAPI()
    {
        $this->setupMockedLogger([
            LogConstants::PRATICA_UPDATED_STATUS_FROM_GPA,
        ]);
        $user = $user = $this->createCPSUser(true);
        $pratica = $this->createPratica($user);
        $initialStatusCount = $pratica->getStoricoStati()->count();

        $rawStatusChange = [
            'evento' => 200,
            'timestamp' => 123,
            'responsabile' => 'Contessa Serbelloni Mazzanti Viendalmare',
            'operatore' => 'pippo',
            'struttura' => 'Anagrafe',
        ];
        $this->client->request(
            'POST',
            $this->formatPraticaStatusUpdateRoute($pratica),
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => 'gpa',
                'PHP_AUTH_PW' => 'gpapass',
            ),
            json_encode(
                $rawStatusChange
            )
        );

        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->em->refresh($pratica);
        $finalStatusCount = $pratica->getStoricoStati()->count();
        $this->assertEquals($initialStatusCount+1, $finalStatusCount);
        $newStatusTimestamp = $pratica->getLatestTimestampForStatus($rawStatusChange['evento']);
        $this->assertEquals($rawStatusChange['timestamp'], $newStatusTimestamp);
        $statoCambiato = $pratica->getStoricoStati()[$newStatusTimestamp];
        $this->assertContains([$rawStatusChange['evento'], $rawStatusChange], $statoCambiato);
    }


    /**
     * @test
     */
    public function testPraticaStatusThrowsIfMissingBody()
    {
        $this->setupMockedLogger([
            LogConstants::PRATICA_ERROR_IN_UPDATED_STATUS_FROM_GPA,
        ]);
        $user = $user = $this->createCPSUser(true);
        $pratica = $this->createPratica($user);

        $this->client->request(
            'POST',
            $this->formatPraticaStatusUpdateRoute($pratica),
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => 'gpa',
                'PHP_AUTH_PW' => 'gpapass',
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
            LogConstants::PRATICA_ERROR_IN_UPDATED_STATUS_FROM_GPA,
        ]);
        $user = $user = $this->createCPSUser(true);
        $pratica = $this->createPratica($user);

        //missing operatore
        $rawStatusChange = [
            'evento' => 200,
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
                'PHP_AUTH_USER' => 'gpa',
                'PHP_AUTH_PW' => 'gpapass',
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
        $user = $user = $this->createCPSUser(true);
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
        $user = $user = $this->createCPSUser(true);
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
        $route = '/api/'.APIController::CURRENT_API_VERSION.'/pratica/'.$pratica->getId().'/status';

        return $route;
    }
}

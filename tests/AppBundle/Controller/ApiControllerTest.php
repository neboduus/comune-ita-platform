<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Controller\APIController;
use AppBundle\Entity\Allegato;
use AppBundle\Entity\ComponenteNucleoFamiliare;
use AppBundle\Entity\Ente;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use AppBundle\Logging\LogConstants;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\VarDumper;
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
        $this->em->getConnection()->executeQuery('DELETE FROM servizio_erogatori')->execute();
        $this->em->getConnection()->executeQuery('DELETE FROM erogatore_ente')->execute();
        $this->em->getConnection()->executeQuery('DELETE FROM ente_asili')->execute();
        $this->cleanDb(ComponenteNucleoFamiliare::class);
        $this->cleanDb(Pratica::class);
        $this->cleanDb(Allegato::class);
        $this->cleanDb(User::class);
        $this->cleanDb(Ente::class);
        $this->cleanDb(Servizio::class);
    }

    /**
     * @test
     */
    public function testStatusAPI()
    {
        $expectedResponse = (object)[
            'status' => 'ok',
            'version' => APIController::CURRENT_API_VERSION,
        ];
        $this->client->request('GET', '/api/' . APIController::CURRENT_API_VERSION . '/status');

        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertEquals($expectedResponse, $response);
        $this->assertEquals('application/json', $this->client->getResponse()->headers->get('Content-Type'));
    }

    /**
     * @test
     */
    public function testUsageAPI()
    {
        $pratiche = [
            '2019' => [
                $this->createPratica($this->createCPSUser(),null,Pratica::STATUS_SUBMITTED)->setSubmissionTime(1553763008)
                ],
            '2017' => [
        $this->createPratica($this->createCPSUser(),null,Pratica::STATUS_SUBMITTED)->setSubmissionTime(1491004800),
        $this->createPratica($this->createCPSUser(),null,Pratica::STATUS_SUBMITTED)->setSubmissionTime(1491004802)
                ]
        ];

        $expectedResponse = (object)[
            'status' => 'ok',
            'version' => APIController::CURRENT_API_VERSION,
            'count' => (object)[
                '2019' => count($pratiche['2019']),
                '2017' => count($pratiche['2017'])
            ]
        ];
        $this->client->request('GET', '/api/' . APIController::CURRENT_API_VERSION . '/usage');

        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertEquals($expectedResponse, $response);
        $this->assertEquals('application/json', $this->client->getResponse()->headers->get('Content-Type'));
    }

    /**
     * @test
     */
    public function testPostAnnotationsAPI()
    {
        $user = $this->createCPSUser(true);
        $pratica = $this->createPratica($user);
        $notes = "La marianna la va in campagna @#èòàù€’”ß@ł€ ";

        $this->clientRequestAsCPSUser($user, 'POST', '/api/' . APIController::CURRENT_API_VERSION . '/user/' . $pratica->getId() . '/notes', [
            'ContentType' => 'application/json'
        ], [], [], $notes);

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertEquals($notes, $pratica->getUserCompilationNotes());
    }

    /**
     * @test
     */
    public function testGetAnnotationsAPI()
    {
        $user = $this->createCPSUser(true);
        $pratica = $this->createPratica($user);
        $notes = "La marianna la va in campagna @#èòàù€’”ß@ł€ ";
        $pratica->setUserCompilationNotes($notes);
        $this->em->flush();

        $this->clientRequestAsCPSUser($user, 'GET', '/api/' . APIController::CURRENT_API_VERSION . '/user/' . $pratica->getId() . '/notes');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertEquals($notes, $this->client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function testCannotPostNotesIfPraticaIsNotMine()
    {
        $user = $this->createCPSUser(true);
        $user2 = $this->createCPSUser(true);
        $pratica = $this->createPratica($user2);
        $notes = "La marianna la va in campagna @#èòàù€’”ß@ł€ ";

        $this->clientRequestAsCPSUser($user, 'POST', '/api/' . APIController::CURRENT_API_VERSION . '/user/' . $pratica->getId() . '/notes', [
            'ContentType' => 'application/json'
        ], [], [], $notes);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(null, $pratica->getUserCompilationNotes());
    }

    /**
     * @test
     */
    public function testAnnotationsAPIIsProtected()
    {
        $user = $this->createCPSUser(true);
        $pratica = $this->createPratica($user);
        $notes = "La marianna la va in campagna @#èòàù€’”ß@ł€ ";
        $this->client->request('POST', '/api/' . APIController::CURRENT_API_VERSION . '/user/' . $pratica->getId() . '/notes', [
            'ContentType' => 'application/json'
        ], [], [], $notes);

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function testGetServizi()
    {
        $servizio1 = $this->createServizioWithAssociatedErogatori([]);
        $servizio2 = $this->createServizioWithAssociatedErogatori([]);

        $expectedResponse = [
            (object)[
                'name' => $servizio1->getName(),
                'slug' => $servizio1->getSlug(),
            ],
            (object)[
                'name' => $servizio2->getName(),
                'slug' => $servizio2->getSlug(),
            ],
        ];

        $this->client->request('GET', '/api/' . APIController::CURRENT_API_VERSION . '/services');
        $response = json_decode($this->client->getResponse()->getContent(), false);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * @test
     */
    public function testSchedaInformativaAPIIsProtected()
    {
        $enti = $this->createEnti();
        $erogatori = $this->createErogatoreWithEnti($enti);
        $servizio = $this->createServizioWithAssociatedErogatori([$erogatori]);
        $client = static::createClient();
        $client->restart();
        $client->request(
            'GET',
            $this->formatSchedaInformativaUpdateRoute($servizio, $enti[0])
        );

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function testSchedaInformativaAPIIsProtectedWithRoleChecking()
    {
        $enti = $this->createEnti();
        $erogatori = $this->createErogatoreWithEnti($enti);
        $servizio = $this->createServizioWithAssociatedErogatori([$erogatori]);
        $client = static::createClient();
        $client->restart();
        $client->request(
            'GET',
            $this->formatSchedaInformativaUpdateRoute($servizio, $enti[0]),
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => 'ez_no_role',
                'PHP_AUTH_PW' => 'ez',
            )
        );

        $this->assertEquals(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
    }


    /**
     * @test
     */
    public function testSchedaInformativaAPIReturnsErrorIfMissingMandatoryQueryStringParameter()
    {
        $enti = $this->createEnti();
        $erogatori = $this->createErogatoreWithEnti($enti);
        $servizio = $this->createServizioWithAssociatedErogatori([$erogatori]);
        $client = static::createClient();
        $client->restart();
        $url = $this->formatSchedaInformativaUpdateRoute($servizio, $enti[0]);
        $client->request(
            'GET',
            $url,
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => 'ez',
                'PHP_AUTH_PW' => 'ez',
            )
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function testQueryingTheEndpointAddsTheSchedaInformativaToServizioForEnte()
    {
        $remoteUrl = 'http://www.comune.trento.it/api/opendata/v2/content/read/629089';
        $enti = $this->createEnti();
        $ente = $enti[0];
        $erogatori = $this->createErogatoreWithEnti($enti);
        $servizio = $this->createServizioWithAssociatedErogatori([$erogatori]);
        $client = static::createClient();
        $client->restart();
        $url = $this->formatSchedaInformativaUpdateRoute($servizio, $ente, $remoteUrl);
        $client->request(
            'GET',
            $url,
            array(),
            array(),
            array(
                'PHP_AUTH_USER' => 'ez',
                'PHP_AUTH_PW' => 'ez',
            )
        );

        $this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

        $expectedContent = json_decode(file_get_contents($remoteUrl), true);
        $this->assertTrue(array_key_exists('data', $expectedContent));
        $this->assertTrue(array_key_exists('metadata', $expectedContent));

        $this->em->persist($servizio);
        $this->em->refresh($servizio);
        $schedaInformativa = $servizio->getSchedaInformativaPerEnte($ente);
        $this->assertEquals($expectedContent, $schedaInformativa);
    }

    /**
     * @param Servizio $servizio
     * @param Ente $ente
     * @return string
     */
    private function formatSchedaInformativaUpdateRoute(Servizio $servizio, Ente $ente, $remoteUrl = null): string
    {
        $route = '/api/' . APIController::CURRENT_API_VERSION . '/schedaInformativa/' . $servizio->getSlug() . '/' . $ente->getCodiceMeccanografico();

        $remoteUrl ? $route .= '?remote=' . urlencode($remoteUrl) : null;

        return $route;
    }
}

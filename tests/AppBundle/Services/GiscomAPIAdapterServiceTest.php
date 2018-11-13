<?php

namespace Tests\AppBundle\Services;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\SciaPraticaEdilizia;
use AppBundle\Mapper\Giscom\GiscomStatusMapper;
use AppBundle\Services\GiscomAPIAdapterService;
use AppBundle\Services\GiscomAPIMapperService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\VarDumper\VarDumper;
use Tests\AppBundle\Base\AbstractAppTestCase;

class GiscomAPIAdapterServiceTest extends AbstractAppTestCase
{
    public function testServiceExists()
    {
        $giscomApiAdapter = $this->container->get('ocsdc.giscom_api.adapter');
        $this->assertNotNull($giscomApiAdapter);

        $giscomApiAdapter = $this->container->get('ocsdc.giscom_api.adapter_direct');
        $this->assertNotNull($giscomApiAdapter);
    }

    public function testServiceSendsPraticaToGISCOM()
    {
        $pratica = $this->setupPraticaScia([], true);
        $pratica->setStatus(SciaPraticaEdilizia::STATUS_REGISTERED);
        $this->em->flush();

        $guzzleMock = $this->getMockGuzzleClient([
            new Response(201, [], json_encode([
                'Id' => 1234,
                'Stato' => [
                    'Codice' => GiscomStatusMapper::GISCOM_STATUS_PREISTRUTTORIA,
                    'Note' => '',
                ]
            ])),
            new Response(200, [], json_encode([
                $this->getCPSUserBaseData()['codiceFiscale'],
                $this->getCPSUserBaseData()['codiceFiscale'],
                $this->getCPSUserBaseData()['codiceFiscale'],
            ]))
        ]);


        $giscomApiAdapter = new GiscomAPIAdapterService(
            $guzzleMock,
            $this->em,
            $this->getMockLogger(),
            $this->getMockedGiscomMapper($this->em),
            $this->container->get('ocsdc.pratica_status_service'),
            $this->container->get('ocsdc.status_mapper.giscom')
        );
        $response = $giscomApiAdapter->sendPraticaToGiscom($pratica);
        $responseBody = json_decode($response->getBody(), true);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNotNull($responseBody['Id']);
        $this->assertNotNull($responseBody['Stato']);

    }

    public function testServiceSendsPraticaToGISCOMWithPUTIfForIntegrazione()
    {
        $pratica = $this->setupPraticaScia([], true);
        $pratica->setStatus(Pratica::STATUS_PENDING_AFTER_INTEGRATION);
        $this->em->flush();

        $guzzleMock = $this->getMockGuzzleClient([
            new Response(201, [], json_encode([
                'Id' => 1234,
                'Stato' => [
                    'Codice' => GiscomStatusMapper::GISCOM_STATUS_PREISTRUTTORIA,
                    'Note' => '',
                ]
            ])),
            new Response(200, [], json_encode([
                $this->getCPSUserBaseData()['codiceFiscale'],
                $this->getCPSUserBaseData()['codiceFiscale'],
                $this->getCPSUserBaseData()['codiceFiscale'],
            ]))
        ]);

        $logger = $this->getMockLogger();
        $logger->expects($this->at(0))
            ->method('info')
            ->with($this->callback(function ($subject) {
                return strpos($subject, 'Updating') !== false;
            }));


        $giscomApiAdapter = new GiscomAPIAdapterService(
            $guzzleMock,
            $this->em,
            $logger,
            $this->getMockedGiscomMapper($this->em),
            $this->container->get('ocsdc.pratica_status_service'),
            $this->container->get('ocsdc.status_mapper.giscom')
        );
        $response = $giscomApiAdapter->sendPraticaToGiscom($pratica);
        $this->assertEquals(201, $response->getStatusCode());

        $responseBody = json_decode($response->getBody(), true);
        $this->assertNotNull($responseBody['Id']);
        $this->assertNotNull($responseBody['Stato']);
    }

    public function testServiceLogsRemoteError()
    {

        $pratica = $this->setupPraticaScia([], true);
        $guzzleMock = $this->getMockGuzzleClient([new Response(400)]);

        $loggerMock = $this->getMockLogger();
        $loggerMock->expects($this->once())->method('info');
        $loggerMock->expects($this->once())->method('error');


        $giscomApiAdapter = new GiscomAPIAdapterService(
            $guzzleMock,
            $this->em,
            $loggerMock,
            $this->getMockedGiscomMapper($this->em),
            $this->container->get('ocsdc.pratica_status_service'),
            $this->container->get('ocsdc.status_mapper.giscom')
        );

        $exception = null;
        try{
            $giscomApiAdapter->sendPraticaToGiscom($pratica);
        }catch(\Exception $e){
            $exception = $e;
        }

        $this->assertTrue($exception instanceof \Exception);
    }

    public function testServiceLogsRemoteResponse()
    {
        $pratica = $this->setupPraticaScia([], true);
        $pratica->setStatus(SciaPraticaEdilizia::STATUS_REGISTERED);
        $this->em->flush();

        $guzzleMock = $this->getMockGuzzleClient([
            new Response(201, [], json_encode([
                'Id' => 1234,
                'Stato' => [
                    'Codice' => GiscomStatusMapper::GISCOM_STATUS_PREISTRUTTORIA,
                    'Note' => '',
                ]
            ])),
            new Response(200, [], json_encode([
                $this->getCPSUserBaseData()['codiceFiscale'],
                $this->getCPSUserBaseData()['codiceFiscale'],
                $this->getCPSUserBaseData()['codiceFiscale'],
            ]))
        ]);

        $loggerMock = $this->getMockLogger();
        $loggerMock->expects($this->exactly(3))->method('info');

        $giscomApiAdapter = new GiscomAPIAdapterService(
            $guzzleMock,
            $this->em,
            $loggerMock,
            $this->getMockedGiscomMapper($this->em),
            $this->container->get('ocsdc.pratica_status_service'),
            $this->container->get('ocsdc.status_mapper.giscom')
        );
        $response = $giscomApiAdapter->sendPraticaToGiscom($pratica);
        $this->assertEquals(201, $response->getStatusCode());
        $responseBody = json_decode($response->getBody(), true);
        $this->assertNotNull($responseBody['Id']);
        $this->assertNotNull($responseBody['Stato']);
    }

    public function testServiceReadsRemoteCF()
    {
        $pratica = $this->setupPraticaScia();

        $guzzleMock = $this->getMockGuzzleClient([
            new Response(200, [], json_encode([
                $this->getCPSUserBaseData()['codiceFiscale'],
                $this->getCPSUserBaseData()['codiceFiscale'],
                $this->getCPSUserBaseData()['codiceFiscale'],
            ]))
        ]);

        $loggerMock = $this->getMockLogger();
        $loggerMock->expects($this->exactly(2))->method('info');

        $this->assertEquals(0, count($pratica->getRelatedCFs()));

        $giscomApiAdapter = new GiscomAPIAdapterService(
            $guzzleMock,
            $this->em,
            $loggerMock,
            $this->getMockedGiscomMapper($this->em),
            $this->container->get('ocsdc.pratica_status_service'),
            $this->container->get('ocsdc.status_mapper.giscom')
        );

        $response = $giscomApiAdapter->askRelatedCFsforPraticaToGiscom($pratica);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(3, count(json_decode($response->getBody())));

        $this->em->refresh($pratica);
        $this->assertEquals(3, count($pratica->getRelatedCFs()));

    }

    private function getMockedGiscomMapper($em)
    {
        return new GiscomAPIMapperService($em);
    }
}
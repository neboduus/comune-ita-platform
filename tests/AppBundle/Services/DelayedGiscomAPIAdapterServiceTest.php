<?php

namespace Tests\AppBundle\Services;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\ScheduledAction;
use AppBundle\Entity\SciaPraticaEdilizia;
use AppBundle\Mapper\Giscom\GiscomStatusMapper;
use AppBundle\Services\DelayedGiscomAPIAdapterService;
use AppBundle\Services\GiscomAPIAdapterService;
use AppBundle\Services\GiscomAPIMapperService;
use Doctrine\Common\Collections\ArrayCollection;
use GuzzleHttp\Psr7\Response;
use Tests\AppBundle\Base\AbstractAppTestCase;

class DelayedGiscomAPIAdapterServiceTest extends AbstractAppTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->cleanDb(ScheduledAction::class);
    }

    public function testServiceExists()
    {
        $giscomApiAdapter = $this->container->get('ocsdc.giscom_api.adapter');
        $this->assertNotNull($giscomApiAdapter);

        $giscomApiAdapter = $this->container->get('ocsdc.giscom_api.adapter_delayed');
        $this->assertNotNull($giscomApiAdapter);
    }

    public function testServiceSendsPraticaToGISCOM()
    {
        $pratica = $this->setupPraticaScia([], true);
        $pratica->setStatus(SciaPraticaEdilizia::STATUS_REGISTERED);
        $this->em->persist($pratica);
        $this->em->flush();

        $guzzleMock = $this->getMockGuzzleClient([
            new Response(201, [], json_encode([
                'Id' => 1234,
                'Stato' => [
                    'Codice' => GiscomStatusMapper::GISCOM_STATUS_PREISTRUTTORIA,
                    'Note' => '',
                ]
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
        $delayedGiscomApiAdapter = new DelayedGiscomAPIAdapterService(
            $giscomApiAdapter, $this->em, $this->getMockLogger(), $this->container->get('ocsdc.schedule_action_service')
        );
        $delayedGiscomApiAdapter->sendPraticaToGiscom($pratica);
        $this->executeCron($delayedGiscomApiAdapter);
        /**
         * if we reached this point all is fine
         */
        $this->assertTrue(true);
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
        $delayedGiscomApiAdapter = new DelayedGiscomAPIAdapterService(
            $giscomApiAdapter, $this->em, $this->getMockLogger(), $this->container->get('ocsdc.schedule_action_service')
        );
        $delayedGiscomApiAdapter->sendPraticaToGiscom($pratica);

        $exception = null;
        try{
            $this->executeCron($delayedGiscomApiAdapter);
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
        $delayedGiscomApiAdapter = new DelayedGiscomAPIAdapterService(
            $giscomApiAdapter, $this->em, $this->getMockLogger(), $this->container->get('ocsdc.schedule_action_service')
        );
        $delayedGiscomApiAdapter->sendPraticaToGiscom($pratica);
        $this->executeCron($delayedGiscomApiAdapter);
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
        $delayedGiscomApiAdapter = new DelayedGiscomAPIAdapterService(
            $giscomApiAdapter, $this->em, $this->getMockLogger(), $this->container->get('ocsdc.schedule_action_service')
        );
        $delayedGiscomApiAdapter->askRelatedCFsforPraticaToGiscom($pratica);
        $this->executeCron($delayedGiscomApiAdapter);

        $this->em->refresh($pratica);
        $this->assertEquals(3, count($pratica->getRelatedCFs()));

    }

    private function executeCron(DelayedGiscomAPIAdapterService $delayedGiscomApiAdapter)
    {
        $scheduleService = $this->container->get('ocsdc.schedule_action_service');
        $actions = $scheduleService->getActions();
        foreach($actions as $action){
            if ($action->getService() == 'ocsdc.giscom_api.adapter'){
                $delayedGiscomApiAdapter->executeScheduledAction($action);
                $scheduleService->markAsDone($action);
            }
        }
        $scheduleService->done();
    }

    private function getMockedGiscomMapper($em)
    {
        return new GiscomAPIMapperService($em);
    }
}

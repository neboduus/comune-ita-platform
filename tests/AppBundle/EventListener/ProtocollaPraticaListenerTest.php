<?php

namespace Test\AppBundle\EventListener;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\ScheduledAction;
use AppBundle\Entity\User;
use AppBundle\Event\PraticaOnChangeStatusEvent;
use AppBundle\EventListener\ProtocollaPraticaListener;
use AppBundle\Services\ProtocolloService;
use Tests\AppBundle\Base\AbstractAppTestCase;

class ProtocollaPraticaListenerTest extends AbstractAppTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->cleanDb(ScheduledAction::class);
        $this->cleanDb(Allegato::class);
        $this->cleanDb(Pratica::class);
        $this->cleanDb(User::class);
    }

    public function testProtocollaPraticaListenerOnStatusSubmitted()
    {
        $cpsUser = $this->createCPSUser();
        $pratica = $this->createPratica($cpsUser);
        $pratica->setStatus(Pratica::STATUS_DRAFT);

        $event = new PraticaOnChangeStatusEvent($pratica, Pratica::STATUS_SUBMITTED);

        $mockProtocollo = $this->getMockBuilder(ProtocolloService::class)->disableOriginalConstructor()->getMock();
        $mockProtocollo->expects($this->once())
            ->method('protocollaPratica')
            ->with($pratica);

        $listener = new ProtocollaPraticaListener($mockProtocollo, $this->getMockLogger());
        $listener->onStatusChange($event);
    }

    public function testProtocollaPraticaListenerOnStatusUpdated()
    {
        $cpsUser = $this->createCPSUser();
        $pratica = $this->createPratica($cpsUser);
        $pratica->setStatus(Pratica::STATUS_DRAFT);

        $event = new PraticaOnChangeStatusEvent($pratica, Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION);

        $mockProtocollo = $this->getMockBuilder(ProtocolloService::class)->disableOriginalConstructor()->getMock();
        $mockProtocollo->expects($this->once())
            ->method('protocollaAllegatiIntegrazione')
            ->with($pratica);

        $listener = new ProtocollaPraticaListener($mockProtocollo, $this->getMockLogger());
        $listener->onStatusChange($event);
    }

    public function protocolloPraticaOnStatusWaitDataProvider()
    {
        return [
            [Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE],
            [Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE],
        ];
    }

    /**
     * @dataProvider protocolloPraticaOnStatusWaitDataProvider
     *
     * @param $status
     */
    public function testProtocollaPraticaListenerOnStatusWait($status)
    {
        $cpsUser = $this->createCPSUser();
        $pratica = $this->createPratica($cpsUser);

        $event = new PraticaOnChangeStatusEvent($pratica, $status);

        $mockProtocollo = $this->getMockBuilder(ProtocolloService::class)->disableOriginalConstructor()->getMock();
        $mockProtocollo->expects($this->once())
            ->method('protocollaRisposta')
            ->with($pratica);

        $listener = new ProtocollaPraticaListener($mockProtocollo, $this->getMockLogger());
        $listener->onStatusChange($event);
    }

    public function protocolloPraticaNotSubmittedDataProvider()
    {
        return [
            [Pratica::STATUS_CANCELLED],
            [Pratica::STATUS_DRAFT],
            [Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION],
            [Pratica::STATUS_COMPLETE],
            [Pratica::STATUS_PENDING],
            [Pratica::STATUS_REGISTERED],
        ];
    }

    /**
     * @dataProvider protocolloPraticaNotSubmittedDataProvider
     *
     * @param $status
     */
    public function testProtocolloPraticaNotSubmittedListener($status)
    {
        $cpsUser = $this->createCPSUser();
        $pratica = $this->createPratica($cpsUser);

        $event = new PraticaOnChangeStatusEvent($pratica, $status);

        $mockProtocollo = $this->getMockBuilder(ProtocolloService::class)->disableOriginalConstructor()->getMock();
        $listener = new ProtocollaPraticaListener($mockProtocollo, $this->getMockLogger());
        $mockProtocollo->expects($this->never())
            ->method('protocollaPratica')
            ->with($pratica);

        $listener->onStatusChange($event);
    }
}
<?php

namespace Test\AppBundle\EventListener;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\ScheduledAction;
use AppBundle\Entity\User;
use AppBundle\Event\PraticaOnChangeStatusEvent;
use AppBundle\EventListener\ProtocolloPraticaListener;
use AppBundle\Services\ProtocolloService;
use Tests\AppBundle\Base\AbstractAppTestCase;

class ProtocolloPraticaListenerTest extends AbstractAppTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->cleanDb(ScheduledAction::class);
        $this->cleanDb(Allegato::class);
        $this->cleanDb(Pratica::class);
        $this->cleanDb(User::class);
    }

    public function testProtocolloPraticaListener()
    {
        $cpsUser = $this->createCPSUser();
        $pratica = $this->createPratica($cpsUser);
        $pratica->setStatus(Pratica::STATUS_SUBMITTED);

        $event = new PraticaOnChangeStatusEvent($pratica, Pratica::STATUS_SUBMITTED);

        $mockProtocollo = $this->getMockBuilder(ProtocolloService::class)->disableOriginalConstructor()->getMock();
        $mockProtocollo->expects($this->once())
                       ->method('protocollaPratica')
                       ->with($pratica);

        $listener = new ProtocolloPraticaListener($mockProtocollo, $this->getMockLogger());
        $listener->onStatusChange($event);
    }

    public function protocolloPraticaNotSubmittedDataProvider()
    {
        return [
            [Pratica::STATUS_CANCELLED],
            [Pratica::STATUS_DRAFT],
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
        $listener = new ProtocolloPraticaListener($mockProtocollo, $this->getMockLogger());
        $mockProtocollo->expects($this->never())
                       ->method('protocollaPratica')
                       ->with($pratica);

        $listener->onStatusChange($event);
    }
}

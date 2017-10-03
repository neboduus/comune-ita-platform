<?php
namespace Test\AppBundle\EventListener;


use AppBundle\Event\ProtocollaPraticaSuccessEvent;
use AppBundle\EventListener\GiscomSendPraticaListener;
use AppBundle\Services\GiscomAPIAdapterService;
use Tests\AppBundle\Base\AbstractAppTestCase;

class GiscomPraticaListenerTest extends AbstractAppTestCase
{
    /**
     *
     * @param $status
     */
    public function testGiscomSendPraticaListenerSendsPratica()
    {
        $pratica = $this->setupPraticaScia();

        $event = new ProtocollaPraticaSuccessEvent($pratica);

        $mockGiscomAdapter = $this->getMockBuilder(GiscomAPIAdapterService::class)->disableOriginalConstructor()->getMock();
        $listener = new GiscomSendPraticaListener($mockGiscomAdapter, $this->getMockLogger());
        $mockGiscomAdapter->expects($this->once())
            ->method('sendPraticaToGiscom')
            ->with($pratica);

        $listener->onPraticaProtocollata($event);
    }

    public function testGiscomSendPraticaListenerIgnoresPraticaThatAreNotManagedByGiscom()
    {
        $pratica = $this->createPratica($this->createCPSUser());

        $event = new ProtocollaPraticaSuccessEvent($pratica);

        $mockGiscomAdapter = $this->getMockBuilder(GiscomAPIAdapterService::class)->disableOriginalConstructor()->getMock();
        $listener = new GiscomSendPraticaListener($mockGiscomAdapter, $this->getMockLogger());
        $mockGiscomAdapter->expects($this->never())
            ->method('sendPraticaToGiscom')
            ->with($pratica);

        $listener->onPraticaProtocollata($event);
    }
}

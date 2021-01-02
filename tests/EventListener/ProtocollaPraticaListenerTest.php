<?php

namespace Test\App\EventListener;

use App\Entity\Allegato;
use App\Entity\Pratica;
use App\Entity\ScheduledAction;
use App\Entity\User;
use App\Event\PraticaOnChangeStatusEvent;
use App\EventListener\ProtocollaPraticaListener;
use App\Services\PraticaStatusService;
use App\Services\ProtocolloService;
use App\Tests\Base\AbstractAppTestCase;

class ProtocollaPraticaListenerTest extends AbstractAppTestCase
{

  public function testProtocollaPraticaListenerOnStatusSubmitted()
  {
    $cpsUser = $this->createCPSUser();
    $pratica = $this->createPratica($cpsUser);
    $pratica->setStatus(Pratica::STATUS_DRAFT);

    $event = new PraticaOnChangeStatusEvent($pratica, Pratica::STATUS_SUBMITTED, $pratica->getStatus());

    $mockProtocollo = $this->getMockBuilder(ProtocolloService::class)->disableOriginalConstructor()->getMock();
    $mockProtocollo->expects($this->once())
      ->method('protocollaPratica')
      ->with($pratica);

    $mockStatusService = $this->getMockBuilder(PraticaStatusService::class)->disableOriginalConstructor()->getMock();

    $listener = new ProtocollaPraticaListener($mockProtocollo, $mockStatusService, $this->getMockLogger());
    $listener->onStatusChange($event);
  }

  public function testProtocollaPraticaListenerOnStatusUpdated()
  {
    $cpsUser = $this->createCPSUser();
    $pratica = $this->createPratica($cpsUser);
    $pratica->setStatus(Pratica::STATUS_DRAFT);

    $event = new PraticaOnChangeStatusEvent(
      $pratica, Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION, $pratica->getStatus()
    );

    $mockProtocollo = $this->getMockBuilder(ProtocolloService::class)->disableOriginalConstructor()->getMock();
    $mockProtocollo->expects($this->once())
      ->method('protocollaAllegatiIntegrazione')
      ->with($pratica);

    $mockStatusService = $this->getMockBuilder(PraticaStatusService::class)->disableOriginalConstructor()->getMock();

    $listener = new ProtocollaPraticaListener($mockProtocollo, $mockStatusService, $this->getMockLogger());
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

    $event = new PraticaOnChangeStatusEvent($pratica, $status, $pratica->getStatus());

    $mockProtocollo = $this->getMockBuilder(ProtocolloService::class)->disableOriginalConstructor()->getMock();
    $mockProtocollo->expects($this->once())
      ->method('protocollaRisposta')
      ->with($pratica);

    $mockStatusService = $this->getMockBuilder(PraticaStatusService::class)->disableOriginalConstructor()->getMock();

    $listener = new ProtocollaPraticaListener($mockProtocollo, $mockStatusService, $this->getMockLogger());
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

    $event = new PraticaOnChangeStatusEvent($pratica, $status, $pratica->getStatus());

    $mockProtocollo = $this->getMockBuilder(ProtocolloService::class)->disableOriginalConstructor()->getMock();
    $mockStatusService = $this->getMockBuilder(PraticaStatusService::class)->disableOriginalConstructor()->getMock();
    $listener = new ProtocollaPraticaListener($mockProtocollo, $mockStatusService, $this->getMockLogger());
    $mockProtocollo->expects($this->never())
      ->method('protocollaPratica')
      ->with($pratica);

    $listener->onStatusChange($event);
  }
}

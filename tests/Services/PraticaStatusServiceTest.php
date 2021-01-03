<?php

namespace App\Tests\Services;

use App\Entity\Pratica;
use App\Entity\StatusChange;
use App\Services\PraticaStatusService;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use App\Tests\Base\AbstractAppTestCase;

class PraticaStatusServiceTest extends AbstractAppTestCase
{

  public function testPraticaStatusServiceChangeStatus()
  {
    $user = $this->createCPSUser();
    $pratica = $this->createPratica($user);
    $service = $this->getMockPraticaStatusService();
    $service->setNewStatus($pratica, Pratica::STATUS_PRE_SUBMIT);

    $this->assertEquals(Pratica::STATUS_PRE_SUBMIT, $pratica->getStatus());
  }

  public function testPraticaStatusServiceCanStoreAMessageComingFromTheStatusChange()
  {
    $user = $this->createCPSUser();
    $pratica = $this->createPratica($user);
    $message = 'Some nice things happened because of this and that';
    $statusChange = new StatusChange(
      [
        'evento' => 'aaa',
        'message' => $message,
        'operatore' => 'abc',
        'responsabile' => 'def',
        'struttura' => 'ghi',
        'timestamp' => time(),
      ]
    );
    $service = $this->getMockPraticaStatusService(4);
    $service->setNewStatus($pratica, Pratica::STATUS_PRE_SUBMIT);
    $service->setNewStatus($pratica, Pratica::STATUS_SUBMITTED);
    $service->setNewStatus($pratica, Pratica::STATUS_PENDING);
    $service->setNewStatus($pratica, Pratica::STATUS_CANCELLED, $statusChange);

    $this->assertEquals(Pratica::STATUS_CANCELLED, $pratica->getStatus());

    $firstStatusChange = $pratica->getStoricoStati()->first()[0];
    $this->assertNull($firstStatusChange[1]['message']);

    $lastStatusChange = $pratica->getStoricoStati()->first()[3];
    $this->assertEquals($lastStatusChange[1]['message'], $message);
  }

  private function getMockPraticaStatusService($count = 1)
  {
    $dispatcher = $this->getMockBuilder(TraceableEventDispatcher::class)->disableOriginalConstructor()->getMock();
    $dispatcher->expects($this->exactly($count))->method('dispatch');

    return
      new PraticaStatusService(
        $this->em,
        $this->getMockLogger(),
        $dispatcher
      );
  }

}

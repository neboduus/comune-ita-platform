<?php

namespace Tests\AppBundle\Services;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\RichiestaIntegrazione;
use AppBundle\Entity\ScheduledAction;
use AppBundle\Entity\User;
use AppBundle\Protocollo\InforProtocolloHandler;
use AppBundle\Protocollo\PiTreProtocolloHandler;
use AppBundle\Services\ProtocolloService;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Tests\AppBundle\Base\AbstractAppTestCase;

class ProtocolloServiceTest extends AbstractAppTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->cleanDb(RichiestaIntegrazione::class);
        $this->cleanDb(ScheduledAction::class);
        $this->cleanDb(Pratica::class);
        $this->cleanDb(Allegato::class);
        $this->cleanDb(User::class);
    }

    /**
     * @test
     */
    public function testProtocolloServiceSendPratica()
    {
        $expectedAllegati = 3;
        $responses = [$this->getPiTreSuccessResponse(), $this->getPiTreSuccessResponse()];
        for ($i = 1; $i <= $expectedAllegati; $i++) {
            $responses[] = $this->getPiTreSuccessResponse();
        }

        $protocollo = $this->getMockProtocollo($responses);

        $user = $this->createCPSUser();
        $pratica = $this->createSubmittedPraticaForUser($user);

        $protocollo->protocollaPratica($pratica);

        $this->assertEquals(Pratica::STATUS_REGISTERED, $pratica->getStatus());
        $this->assertNotEquals(null, $pratica->getNumeroProtocollo());
        $this->assertNotEquals(null, $pratica->getIdDocumentoProtocollo());

        $allegati = $this->setupNeededAllegatiForAllInvolvedUsers($expectedAllegati, $user);
        foreach ($allegati as $allegato) {
            $pratica->addAllegato($allegato);
            $protocollo->protocollaAllegato($pratica, $allegato);
        }

        $this->assertEquals($expectedAllegati+1, count($pratica->getNumeriProtocollo()));
    }

    /**
     * @test
     */
    public function testProtocolloServiceSendPraticaToInfor()
    {
        $this->markTestSkipped('Infor Protocol handler needs to be refactored to use Guzzle clients (so that they can be mocked)');
        $expectedAllegati = 3;
        $responses = [$this->getInforSuccessResponse()];

        $inforhandler = new InforProtocolloHandler($this->getMockLogger(),'a', 'b', 'c@c.c', 'http://wsdl.com?wsdl', 'http://soap.sucks' );

        $protocollo = $this->getMockProtocollo($responses, null, $inforhandler);

        $user = $this->createCPSUser();
        $pratica = $this->createSubmittedPraticaForUser($user);

        $protocollo->protocollaPratica($pratica);

        $this->assertEquals(Pratica::STATUS_REGISTERED, $pratica->getStatus());
        $this->assertNotEquals(null, $pratica->getNumeroProtocollo());
        $this->assertNotEquals(null, $pratica->getIdDocumentoProtocollo());

        $allegati = $this->setupNeededAllegatiForAllInvolvedUsers($expectedAllegati, $user);
        foreach ($allegati as $allegato) {
            $pratica->addAllegato($allegato);
            $protocollo->protocollaAllegato($pratica, $allegato);
        }

        $this->assertEquals($expectedAllegati, count($pratica->getNumeriProtocollo()));
    }

    /**
     * @test
     */
    public function testProtocolloServiceDispatchEvent()
    {
        $expectedAllegati = 2;
        $responses = [
            $this->getPiTreSuccessResponse(),
            $this->getPiTreSuccessResponse(),
            $this->getPiTreSuccessResponse()
        ];
        for ($i = 1; $i <= $expectedAllegati; $i++) {
            $responses[] = $this->getPiTreSuccessResponse();
        }

        $dispatcher = $this->getMockBuilder(TraceableEventDispatcher::class)->disableOriginalConstructor()->getMock();
        $dispatcher->expects($this->exactly(2))->method('dispatch');

        $protocollo = $this->getMockProtocollo($responses, $dispatcher);

        $user = $this->createCPSUser();
        $pratica = $this->createSubmittedPraticaForUser($user);

        $protocollo->protocollaPratica($pratica);

        $allegati = $this->setupNeededAllegatiOperatoreForAllInvolvedUsers(1, $user);
        foreach ($allegati as $allegato) {
            $pratica->addAllegatoOperatore($allegato);
        }

        $risposta = $this->setupRispostaOperatoreForAllInvolvedUsers($user);
        $pratica->addRispostaOperatore($risposta);
        $protocollo->protocollaRisposta($pratica);
    }

    /**
     * @test
     * @expectedException \AppBundle\Protocollo\Exception\ResponseErrorException
     */
    public function testProtocolloServiceWrongResponse()
    {
        $protocollo = $this->getMockProtocollo([$this->getPiTreErrorResponse()]);
        $user = $this->createCPSUser();
        $pratica = $this->createSubmittedPraticaForUser($user);

        $protocollo->protocollaPratica($pratica);
    }

    /**
     * @test
     * @expectedException \AppBundle\Protocollo\Exception\AlreadySentException
     */
    public function testProtocolloServiceCanNotSendPraticaWithNumeroProtocollo()
    {
        $protocollo = $this->getMockProtocollo();
        $user = $this->createCPSUser();
        $pratica = $this->createSubmittedPraticaForUser($user);
        $pratica->setNumeroProtocollo('test');

        $protocollo->protocollaPratica($pratica);
    }

//    /**
//     * @test
//     * @expectedException \AppBundle\Protocollo\Exception\InvalidStatusException
//     */
//    public function testProtocolloServiceCanNotSendPraticaNotSubmitted()
//    {
//        $protocollo = $this->getMockProtocollo();
//        $user = $this->createCPSUser();
//        $pratica = $this->createPratica($user);
//
//        $protocollo->protocollaPratica($pratica);
//    }

    /**
     * @test
     * @expectedException \AppBundle\Protocollo\Exception\ParentNotRegisteredException
     */
    public function testProtocolloServiceCanNotSendAllegatoInPraticaNotSubmitted()
    {
        $protocollo = $this->getMockProtocollo();
        $user = $this->createCPSUser();
        $pratica = $this->createPratica($user);
        $allegati = $this->setupNeededAllegatiForAllInvolvedUsers(1, $user);

        foreach ($allegati as $allegato) {
            $pratica->addAllegato($allegato);
            $protocollo->protocollaAllegato($pratica, $allegato);
        }
    }

    /**
     * @test
     * @expectedException \AppBundle\Protocollo\Exception\AlreadyUploadException
     */
    public function testProtocolloServiceCanNotUploadAllegatoTwice()
    {
        $protocollo = $this->getMockProtocollo([$this->getPiTreSuccessResponse(), $this->getPiTreSuccessResponse(),$this->getPiTreSuccessResponse()]);
        $user = $this->createCPSUser();
        $pratica = $this->createSubmittedPraticaForUser($user);
        $protocollo->protocollaPratica($pratica);

        $allegati = $this->setupNeededAllegatiForAllInvolvedUsers(1, $user);

        foreach ($allegati as $allegato) {
            $pratica->addAllegato($allegato);
            $protocollo->protocollaAllegato($pratica, $allegato);
        }

        foreach ($allegati as $allegato) {
            $pratica->addAllegato($allegato);
            $protocollo->protocollaAllegato($pratica, $allegato);
        }
    }

    private function getMockProtocollo($responses = array(), $dispatcher = null, $protocollohandlerMock = null)
    {
        if (!$dispatcher){
            $dispatcher = $this->container->get('event_dispatcher');
        }
        return
            new ProtocolloService(
                $protocollohandlerMock ?? new PiTreProtocolloHandler($this->getMockGuzzleClient($responses)),
                $this->em,
                $this->getMockLogger(),
                $dispatcher
            );

    }


}

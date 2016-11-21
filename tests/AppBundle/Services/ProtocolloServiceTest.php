<?php

namespace Tests\AppBundle\Services;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\ScheduledAction;
use AppBundle\Entity\User;
use AppBundle\Protocollo\PiTreProtocolloHandler;
use AppBundle\Services\ProtocolloService;
use Tests\AppBundle\Base\AbstractAppTestCase;

class ProtocolloServiceTest extends AbstractAppTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->cleanDb(ScheduledAction::class);
        $this->cleanDb(Allegato::class);
        $this->cleanDb(Pratica::class);
        $this->cleanDb(User::class);
    }

    /**
     * @test
     */
    public function testProtocolloServiceSendPratica()
    {
        $expectedAllegati = 3;
        $responses = [$this->getPiTreSuccessResponse()];
        for ($i = 1; $i <= $expectedAllegati; $i++) {
            $responses[] = $this->getPiTreSuccessResponse();
        }
        $protocollo = $this->getMockProtocollo($responses);

        $user = $this->createCPSUser();
        $pratica = $this->createSubmittedPraticaForUser($user);

        $protocollo->protocollaPratica($pratica);

        $this->assertEquals(Pratica::STATUS_REGISTERED, $pratica->getStatus());
        $this->assertNotEquals(null, $pratica->getNumeroProtocollo());
        $this->assertNotEquals(null, $pratica->getNumeroFascicolo());

        $allegati = $this->setupNeededAllegatiForAllInvolvedUsers($expectedAllegati, $user);
        foreach ($allegati as $allegato) {
            $pratica->addAllegato($allegato);
            $protocollo->protocollaAllegato($pratica, $allegato);
        }

        $this->assertEquals($expectedAllegati, count($pratica->getNumeriProtocollo()));
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
    public function testProtocolloServiceCanNotSendPraticaWithNumeroFascicolo()
    {
        $protocollo = $this->getMockProtocollo();
        $user = $this->createCPSUser();
        $pratica = $this->createSubmittedPraticaForUser($user);
        $pratica->setNumeroFascicolo('test');

        $protocollo->protocollaPratica($pratica);
    }

    /**
     * @test
     * @expectedException \AppBundle\Protocollo\Exception\InvalidStatusException
     */
    public function testProtocolloServiceCanNotSendPraticaNotSubmitted()
    {
        $protocollo = $this->getMockProtocollo();
        $user = $this->createCPSUser();
        $pratica = $this->createPratica($user);

        $protocollo->protocollaPratica($pratica);
    }

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
        $protocollo = $this->getMockProtocollo([$this->getPiTreSuccessResponse(), $this->getPiTreSuccessResponse()]);
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

    private function getMockProtocollo($responses = array())
    {
        return
            new ProtocolloService(
                new PiTreProtocolloHandler($this->getMockGuzzleClient($responses)),
                $this->em,
                $this->getMockLogger()
            );

    }

}

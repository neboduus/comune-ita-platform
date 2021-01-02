<?php

namespace App\Tests\Services;


use App\Entity\Allegato;
use App\Entity\Ente;
use App\Entity\OperatoreUser;
use App\Entity\Pratica;
use App\Entity\RichiestaIntegrazioneDTO;
use App\Entity\Servizio;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use App\Tests\Base\AbstractAppTestCase;

class PraticaIntegrationServiceTest extends AbstractAppTestCase
{

  public function testRequestIntegration()
  {
    $user = $this->createCPSUser();
    $pratica = $this->createPratica($user);
    $pratica->setStatus(Pratica::STATUS_PENDING);

    $operatore = $this->createOperatoreUser(
      'test',
      'test',
      $pratica->getEnte(),
      new ArrayCollection([$pratica->getServizio()])
    );

    $message = "la mia richiesta di integrazione di test";
    $payload = ['test' => ['a', 'b', 'c']];

    $service = static::$container->get('ocsdc.pratica_integration_service');
    $request = new RichiestaIntegrazioneDTO($payload, $operatore, $message);

    $service->requestIntegration($pratica, $request);

    $this->assertTrue($pratica->haUnaRichiestaDiIntegrazioneAttiva());
    $this->assertNotNull($pratica->getRichiestaDiIntegrazioneAttiva());

    $this->assertEquals($message, $pratica->getRichiestaDiIntegrazioneAttiva()->getDescription());
    $this->assertEquals($payload, $pratica->getRichiestaDiIntegrazioneAttiva()->getPayload());
  }

}

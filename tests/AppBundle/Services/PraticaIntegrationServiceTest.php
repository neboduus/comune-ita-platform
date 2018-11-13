<?php

namespace Tests\AppBundle\Services;


use AppBundle\Entity\Allegato;
use AppBundle\Entity\Ente;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\RichiestaIntegrazioneDTO;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Tests\AppBundle\Base\AbstractAppTestCase;

class PraticaIntegrationServiceTest extends AbstractAppTestCase
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        system('rm -rf '.__DIR__."/../../../var/uploads/pratiche/allegati/*");

        $this->userProvider = $this->container->get('ocsdc.cps.userprovider');
        $this->em->getConnection()->executeQuery('DELETE FROM servizio_erogatori')->execute();
        $this->em->getConnection()->executeQuery('DELETE FROM erogatore_ente')->execute();
        $this->em->getConnection()->executeQuery('DELETE FROM ente_asili')->execute();
        $this->cleanDb(Allegato::class);
        $this->cleanDb(Pratica::class);
        $this->cleanDb(Servizio::class);
        $this->cleanDb(OperatoreUser::class);
        $this->cleanDb(Ente::class);
        $this->cleanDb(User::class);
    }

    public function testRequestIntegration()
    {
        $user = $this->createCPSUser();
        $pratica = $this->createPratica($user);
        $pratica->setStatus(Pratica::STATUS_PENDING);
        $this->em->persist($pratica);
        $this->em->flush();

        $operatore = $this->createOperatoreUser('test', 'test', $pratica->getEnte(), new ArrayCollection([$pratica->getServizio()]));

        $message = "la mia richiesta di integrazione di test";
        $payload = ['test' => ['a','b','c']];

        $service = $this->container->get('ocsdc.pratica_integration_service');
        $request = new RichiestaIntegrazioneDTO($payload, $operatore, $message);

        $service->requestIntegration($pratica, $request);

        $this->assertTrue($pratica->haUnaRichiestaDiIntegrazioneAttiva());
        $this->assertNotNull($pratica->getRichiestaDiIntegrazioneAttiva());

        $this->assertEquals($message, $pratica->getRichiestaDiIntegrazioneAttiva()->getDescription());
        $this->assertEquals($payload, $pratica->getRichiestaDiIntegrazioneAttiva()->getPayload());
    }

}
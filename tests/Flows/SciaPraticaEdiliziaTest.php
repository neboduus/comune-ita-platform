<?php

namespace Tests\Flows;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\SciaPraticaEdilizia;
use AppBundle\Entity\ComponenteNucleoFamiliare;
use AppBundle\Entity\Ente;
use AppBundle\Entity\ModuloCompilato;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use AppBundle\Services\CPSUserProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Tests\AppBundle\Base\AbstractAppTestCase;


class SciaPraticaEdiliziaTest extends AbstractAppTestCase
{
    /**
     * @var CPSUserProvider
     */
    protected $userProvider;

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
        $this->cleanDb(Pratica::class);
        $this->cleanDb(Allegato::class);
        $this->cleanDb(Servizio::class);
        $this->cleanDb(OperatoreUser::class);
        $this->cleanDb(Ente::class);
        $this->cleanDb(User::class);
    }

    public function testICannotReachTheSciaFormAsLoggedUser() {
        $user = $this->createCPSUser();

        $ente = $this->createEnti()[0];
        $erogatore = $this->createErogatoreWithEnti([$ente]);
        $fqcn = SciaPraticaEdilizia::class;
        $flow = 'ocsdc.form.flow.scia_pratica_edilizia';
        $servizio = $this->createServizioWithErogatore($erogatore, 'Scia', $fqcn, $flow, 'ROLE_SCIA_TECNICO_ACCREDITATO');

        $this->clientRequestAsCPSUser($user, 'GET', $this->router->generate(
            'pratiche_new',
            ['servizio' => $servizio->getSlug()]
        ));
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
    }

    /**
     * Here there was a test called
     * testICanFillOutTheSciaAsLoggedTecnico
     * which has been left marked as incomplete for more than one year
     * It has been deleted for the sake of being clean, it can be retrieved from git history
     */
}

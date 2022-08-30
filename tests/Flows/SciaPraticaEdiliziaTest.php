<?php

namespace Tests\Flows;

use App\Entity\Allegato;
use App\Entity\SciaPraticaEdilizia;
use App\Entity\ComponenteNucleoFamiliare;
use App\Entity\Ente;
use App\Entity\ModuloCompilato;
use App\Entity\OperatoreUser;
use App\Entity\Pratica;
use App\Entity\Servizio;
use App\Entity\User;
use App\Services\CPSUserProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Tests\App\Base\AbstractAppTestCase;


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

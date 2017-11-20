<?php

namespace Tests\Flows;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\AsiloNido;
use AppBundle\Entity\SciaPraticaEdilizia;
use AppBundle\Entity\StatoFamiglia;
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

/**
 * Class StatoFamigliaTest
 */
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
        $this->em->getConnection()->executeQuery('DELETE FROM ente_asili')->execute();
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
     * @test
     */
    public function testICanFillOutTheSciaAsLoggedTecnico()
    {
        //create an ente
        $ente = $this->createEnti()[0];
        $erogatore = $this->createErogatoreWithEnti([$ente]);
        //create the autolettura service bound to that ente
        $fqcn = SciaPraticaEdilizia::class;
        $flow = 'ocsdc.form.flow.scia_pratica_edilizia';
        $servizio = $this->createServizioWithErogatore($erogatore, 'Scia', $fqcn, $flow, 'ROLE_SCIA_TECNICO_ACCREDITATO');

        $user = $this->createCPSUser(true,true, 'ROLE_SCIA_TECNICO_ACCREDITATO');

        $mockMailer = $this->setupSwiftmailerMock([$user]);
        static::$kernel->setKernelModifier(function (KernelInterface $kernel) use ($mockMailer) {
            $kernel->getContainer()->set('swiftmailer.mailer.default', $mockMailer);
        });

        $this->clientRequestAsCPSUser($user, 'GET', $this->router->generate(
            'pratiche_new',
            ['servizio' => $servizio->getSlug()]
        ));
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
        $crawler = $this->client->followRedirect();

        $currentUriParts = explode('/', $this->client->getHistory()->current()->getUri());
        $currentPraticaId = array_pop($currentUriParts);
        $currentPratica = $this->em->getRepository('AppBundle:SciaPraticaEdilizia')->find($currentPraticaId);
        $this->assertEquals(SciaPraticaEdilizia::class, get_class($currentPratica));
        $this->assertEquals(0, $currentPratica->getModuliCompilati()->count());

        $nextButton = $this->translator->trans('button.next', [], 'CraueFormFlowBundle');
        $finishButton = $this->translator->trans('button.finish', [], 'CraueFormFlowBundle');

        if ($currentPratica->getEnte() == null && $this->container->getParameter('prefix') == null) {
            $this->selezioneComune($crawler, $nextButton, $ente, $form, $currentPratica, $erogatore);
        }
        $this->accettazioneIstruzioni($crawler, $nextButton, $form);
        $this->datiRichiedente($crawler, $nextButton, $fillData, $form, true);
        $this->markTestIncomplete('Actual steps need to be tested');
//
//        $form = $crawler->selectButton($finishButton)->form();
//        $this->client->submit($form);
//        $this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
//        $this->client->followRedirect();
//
//        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
//        $this->assertContains($currentPraticaId, $this->client->getRequest()->getRequestUri());
//
//        $this->em->refresh($currentPratica);
//
//        $this->assertEquals(
//            $currentPratica->getRichiedenteNome(),
//            $user->getNome()
//        );
//
//        //modulo stampato
//        $this->assertEquals(1, $currentPratica->getModuliCompilati()->count());
//        $pdfExportedForm = $currentPratica->getModuliCompilati()->get(0);
//        $this->assertNotNull($pdfExportedForm);
//        $this->assertTrue($pdfExportedForm instanceof ModuloCompilato);
//
//        $this->assertNotNull($currentPratica->getSubmissionTime());
//        $submissionDate = new \DateTime();
//        $submissionDate->setTimestamp($currentPratica->getSubmissionTime());
//
//        $this->assertEquals('Modulo '.$currentPratica->getServizio()->getName().' compilato il '.$submissionDate->format($this->container->getParameter('ocsdc_default_datetime_format')), $pdfExportedForm->getDescription());
    }
}

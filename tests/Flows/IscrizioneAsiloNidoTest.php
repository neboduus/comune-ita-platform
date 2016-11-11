<?php

namespace Tests\Flows;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\AsiloNido;
use AppBundle\Entity\ComponenteNucleoFamiliare;
use AppBundle\Entity\Ente;
use AppBundle\Entity\IscrizioneAsiloNido;
use AppBundle\Entity\ModuloCompilato;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use AppBundle\Logging\LogConstants;
use AppBundle\Services\CPSUserProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\VarDumper\VarDumper;
use Tests\AppBundle\Base\AbstractAppTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class PraticaControllerTest
 */
class IscrizioneAsiloNidoTest extends AbstractAppTestCase
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
        $this->em->getConnection()->executeQuery('DELETE FROM servizio_enti')->execute();
        $this->em->getConnection()->executeQuery('DELETE FROM ente_asili')->execute();
        $this->cleanDb(ComponenteNucleoFamiliare::class);
        $this->cleanDb(Allegato::class);
        $this->cleanDb(Pratica::class);
        $this->cleanDb(Servizio::class);
        $this->cleanDb(AsiloNido::class);
        $this->cleanDb(OperatoreUser::class);
        $this->cleanDb(Ente::class);
        $this->cleanDb(User::class);
    }

    /**
     * @test
     */
    public function testICanFillOutTheFormToEnrollMyChildInAsiloNidoAsLoggedUser()
    {
        $ente = $this->createEnteWithAsili();

        $fqcn = IscrizioneAsiloNido::class;
        $flow = 'ocsdc.form.flow.asilonido';
        $servizio = $this->createServizioWithEnte($ente, 'Iscrizione Asilo Nido', $fqcn, $flow);

        $user = $this->createCPSUser();
        $numberOfExpectedAttachments = 3;
        $this->setupNeededAllegatiForAllInvolvedUsers($numberOfExpectedAttachments, $user);

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
        $currentPratica = $this->em->getRepository('AppBundle:IscrizioneAsiloNido')->find($currentPraticaId);
        $this->assertEquals(get_class($currentPratica), IscrizioneAsiloNido::class);
        $this->assertEquals(0, $currentPratica->getModuliCompilati()->count());

        $nextButton = $this->translator->trans('button.next', [], 'CraueFormFlowBundle');
        $finishButton = $this->translator->trans('button.finish', [], 'CraueFormFlowBundle');

        $this->selezioneComune($crawler, $nextButton, $ente, $form);
        $this->accettazioneIstruzioni($crawler, $nextButton, $form);
        /** @var AsiloNido $asiloSelected*/
        $this->selezioneAsilo($ente, $crawler, $nextButton, $asiloSelected, $form);
        $this->terminiFruizione($asiloSelected, $crawler, $nextButton, $form);
        $this->selezioneOrari($asiloSelected, $crawler, $nextButton, $form);
        $this->datiRichiedente($crawler, $nextButton, $fillData, $form);
        $this->datiBambino($crawler, $nextButton, $form);
        $this->composizioneNucleoFamiliare($crawler, $nextButton, $form, 0, 5);

        $allegati = $this->em->getRepository('AppBundle:Allegato')->findBy(['owner' => $user]);
        $this->allegati($crawler, $nextButton, $form, $allegati);


        $form = $crawler->selectButton($finishButton)->form();
        $this->client->submit($form);
        $this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
        $this->client->followRedirect();

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
        $this->assertContains($currentPraticaId, $this->client->getRequest()->getRequestUri());

        $this->em->refresh($currentPratica);

        $this->assertEquals(
            $currentPratica->getRichiedenteNome(),
            $user->getNome()
        );

        $this->assertEquals(
            $currentPratica->getStruttura()->getName(),
            $asiloSelected->getName()
        );

        $allegati = $currentPratica->getAllegati()->toArray();
        $this->assertEquals($numberOfExpectedAttachments - 1, count($allegati));

        //modulo stampato
        $this->assertEquals(1, $currentPratica->getModuliCompilati()->count());
        $pdfExportedForm = $currentPratica->getModuliCompilati()->get(0);
        $this->assertNotNull($pdfExportedForm);
        $this->assertTrue($pdfExportedForm instanceof ModuloCompilato);

        $this->assertNotNull($currentPratica->getSubmissionTime());
        $submissionDate = new \DateTime();
        $submissionDate->setTimestamp($currentPratica->getSubmissionTime());

        $this->assertEquals('Modulo '.$currentPratica->getServizio()->getName().' compilato il '.$submissionDate->format('d/m/Y h:i'), $pdfExportedForm->getDescription());
    }

    /**
     * Step specifici di questo flusso
     */

    /**
     * @param Ente $ente
     * @param Crawler $crawler
     * @param $nextButton
     * @param AsiloNido $asiloSelected
     * @param $form
     * @return AsiloNido
     */
    protected function selezioneAsilo($ente, &$crawler, $nextButton, &$asiloSelected, &$form)
    {
        // Selezione del asilo
        $asili = $ente->getAsili();
        $key = rand(1, count($asili)) - 1;
        $asiloSelected = $asili[$key];
        $form = $crawler->selectButton($nextButton)->form(array(
            'iscrizione_asilo_nido_seleziona_nido[struttura]' => $asiloSelected->getId(),
        ));
        $crawler = $this->client->submit($form);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
    }

    /**
     * @param AsiloNido $asiloSelected
     * @param Crawler $crawler
     * @param $nextButton
     * @param $form
     */
    protected function terminiFruizione($asiloSelected, &$crawler, $nextButton, &$form)
    {
        // Termini di fruizione della struttura
        $this->assertContains($asiloSelected->getSchedaInformativa(), $this->client->getResponse()->getContent());

        $form = $crawler->selectButton($nextButton)->form(array(
            'iscrizione_asilo_nido_utilizzo_nido[accetto_utilizzo]' => 1,
        ));
        $crawler = $this->client->submit($form);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
    }

    /**
     * @param AsiloNido $asiloSelected
     * @param Crawler $crawler
     * @param $nextButton
     * @param $form
     */
    protected function selezioneOrari($asiloSelected, &$crawler, $nextButton, &$form)
    {
        $orarioSelected = null;
        foreach ($asiloSelected->getOrari() as $orario) {
            $this->assertContains($orario, $this->client->getResponse()->getContent());
            $orarioSelected = $orario;
        }

        // Test su periodo iscrizione errato
        $form = $crawler->selectButton($nextButton)->form(array(
            'iscrizione_asilo_nido_orari[periodo_iscrizione_da]' => '01-09-2017',
            'iscrizione_asilo_nido_orari[periodo_iscrizione_a]' => '01-09-2016',
            'iscrizione_asilo_nido_orari[struttura_orario]' => $orarioSelected,
        ));
        $crawler = $this->client->submit($form);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");

        $msg = trim($crawler->filter('.alert-danger ul')->first()->text());
        $this->assertEquals($msg, "La data di fine iscrizione deve essere maggiore di quella d'inizio", "Periodo iscrizione errato");

        $form = $crawler->selectButton($nextButton)->form(array(
            'iscrizione_asilo_nido_orari[periodo_iscrizione_da]' => '01-09-2016',
            'iscrizione_asilo_nido_orari[periodo_iscrizione_a]' => '01-09-2017',
            'iscrizione_asilo_nido_orari[struttura_orario]' => $orarioSelected,
        ));
        $crawler = $this->client->submit($form);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
    }


}

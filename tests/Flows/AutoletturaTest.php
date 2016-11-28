<?php

namespace Tests\Flows;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\AsiloNido;
use AppBundle\Entity\AutoletturaAcqua;
use AppBundle\Entity\ComponenteNucleoFamiliare;
use AppBundle\Entity\Ente;
use AppBundle\Entity\ModuloCompilato;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use AppBundle\Services\CPSUserProvider;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Tests\AppBundle\Base\AbstractAppTestCase;

/**
 * Class PraticaControllerTest
 */
class AutoletturaTest extends AbstractAppTestCase
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
    public function testICanFillOutTheAutoletturaAsLoggedUser()
    {
        //create an ente
        $ente = $this->createEnti()[0];
        //create the autolettura service bound to that ente
        $fqcn = AutoletturaAcqua::class;
        $flow = 'ocsdc.form.flow.autoletturaacqua';
        $servizio = $this->createServizioWithEnte($ente, 'Autolettura contatore acqua', $fqcn, $flow);

        $user = $this->createCPSUser();
        $numberOfExpectedAttachments = 0;

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
        $currentPratica = $this->em->getRepository('AppBundle:AutoletturaAcqua')->find($currentPraticaId);
        $this->assertEquals(AutoletturaAcqua::class, get_class($currentPratica));
        $this->assertEquals(0, $currentPratica->getModuliCompilati()->count());

        $nextButton = $this->translator->trans('button.next', [], 'CraueFormFlowBundle');
        $finishButton = $this->translator->trans('button.finish', [], 'CraueFormFlowBundle');

        $this->selezioneComune($crawler, $nextButton, $ente, $form);
        $this->accettazioneIstruzioni($crawler, $nextButton, $form);
        $this->datiRichiedente($crawler, $nextButton, $fillData, $form);
        $this->datiIntestatario($crawler, $nextButton, $fillData, $form);
        $this->datiContatore($crawler, $nextButton, $fillData, $form);
        $this->datiLettura($crawler, $nextButton, $fillData, $form);
        $this->altreComunicazioni($crawler, $nextButton, $fillData, $form);

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

        $allegati = $currentPratica->getAllegati()->toArray();
        $this->assertEquals($numberOfExpectedAttachments, count($allegati));

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
     * @param Crawler $crawler
     * @param $nextButton
     * @param $fillData
     * @param $form
     */
    private function datiIntestatario(&$crawler, $nextButton, &$fillData, &$form)
    {
        $fillData = array();
        $crawler->filter('form[name="autolettura_acqua_intestatario"] input[type="text"]')
            ->each(function ($node, $i) use (&$fillData) {
                self::fillFormInputWithDummyText($node, $i, $fillData);
            });
        $form = $crawler->selectButton($nextButton)->form($fillData);
        $crawler = $this->client->submit($form);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
    }

    /**
     * @param Crawler $crawler
     * @param $nextButton
     * @param $fillData
     * @param $form
     */
    private function datiContatore(&$crawler, $nextButton, &$fillData, &$form)
    {
        $fillData = array();
        $crawler->filter('form[name="autolettura_acqua_contatore"] input[type="text"], form[name="autolettura_acqua_contatore"] input[type="number"]')
            ->each(function ($node, $i) use (&$fillData) {
                self::fillFormInputWithDummyText($node, $i, $fillData);
            });
        $form = $crawler->selectButton($nextButton)->form($fillData);
        $crawler = $this->client->submit($form);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
    }

    /**
     * @param Crawler $crawler
     * @param $nextButton
     * @param $fillData
     * @param $form
     */
    private function datiLettura(&$crawler, $nextButton, &$fillData, &$form)
    {
        $fillData = array();
        $crawler->filter('form[name="autolettura_acqua_contatore"] input[type="text"], form[name="autolettura_acqua_contatore"] input[type="number"]')
                ->each(function ($node, $i) use (&$fillData) {
                    self::fillFormInputWithDummyText($node, $i, $fillData);
                });

        $form = $crawler->selectButton($nextButton)->form($fillData);
        $crawler = $this->client->submit($form);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
    }

    /**
     * @param Crawler $crawler
     * @param $nextButton
     * @param $fillData
     * @param $form
     */
    private function altreComunicazioni(&$crawler, $nextButton, &$fillData, &$form)
    {
        $crawler->filter('form[name="autolettura_acqua_comunicazioni"] textarea')
                ->each(function ($node, $i) use (&$fillData) {
                    self::fillFormInputWithDummyText($node, $i, $fillData);
                });


        $form = $crawler->selectButton($nextButton)->form($fillData);
        $crawler = $this->client->submit($form);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
    }
}

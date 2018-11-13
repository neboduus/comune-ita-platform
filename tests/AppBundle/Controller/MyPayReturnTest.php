<?php

namespace Tests\Flows;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\AsiloNido;
use AppBundle\Entity\AutoletturaAcqua;
use AppBundle\Entity\ComponenteNucleoFamiliare;
use AppBundle\Entity\Ente;
use AppBundle\Entity\IscrizioneAsiloNido;
use AppBundle\Entity\ModuloCompilato;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\PaymentGateway;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use AppBundle\Form\IscrizioneAsiloNido\IscrizioneAsiloNidoFlow;
use AppBundle\Logging\LogConstants;
use AppBundle\Services\CPSUserProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Tests\AppBundle\Base\AbstractAppTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class MyPayReturnTest
 */
class MyPayReturnTest extends AbstractAppTestCase
{
    /**
     * @var CPSUserProvider
     */
    protected $userProvider;

    protected $gateway;

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
        $this->cleanDb(ComponenteNucleoFamiliare::class);
        $this->cleanDb(Allegato::class);
        $this->cleanDb(Pratica::class);
        $this->cleanDb(Servizio::class);
        $this->cleanDb(AsiloNido::class);
        $this->cleanDb(OperatoreUser::class);
        $this->cleanDb(Ente::class);
        $this->cleanDb(User::class);
        $this->cleanDb(PaymentGateway::class);
    }

    /**
     * @test
     */
    public function testICanLandOnCompletedPaymentStepAfterPayingSuccesfullyOnMyPaySide()
    {
        $this->markTestIncomplete('Flow is starting to work, need to test that I cannot skip ahead');
        $ente = $this->createEnteWithAsili();
        $erogatore = $this->createErogatoreWithEnti([$ente]);

        $gateway = new PaymentGateway();
        $gateway->setDescription('MyPay')
        ->setDisclaimer('AAAAA')
        ->setFcqn('AppBundle\Payment\Gateway\MyPay')
        ->setIdentifier('mypay')
        ->setEnabled(true)
        ->setName('MyPay');
        $this->em->persist($gateway);

        $this->gateway = $gateway;

        $fqcn = AutoletturaAcqua::class;
        $flow = 'ocsdc.form.flow.autoletturaacqua';
        $servizio = $this->createServizioWithErogatore($erogatore, 'Autolettura Acqua', $fqcn, $flow);
        $servizio->setPaymentRequired(true);
        $this->em->flush();

        $user = $this->createCPSUser();

        $this->clientRequestAsCPSUser($user, 'GET', $this->router->generate(
            'pratiche_new',
            ['servizio' => $servizio->getSlug()]
        ));
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
        $crawler = $this->client->followRedirect();

        $currentUriParts = explode('/', $this->client->getHistory()->current()->getUri());
        $currentPraticaId = array_pop($currentUriParts);
        $currentPratica = $this->em->getRepository('AppBundle:AutoletturaAcqua')->find($currentPraticaId);
        $this->assertEquals(get_class($currentPratica), AutoletturaAcqua::class);
        $this->assertEquals(0, $currentPratica->getModuliCompilati()->count());

        $nextButton = $this->translator->trans('button.next', [], 'CraueFormFlowBundle');
        $finishButton = $this->translator->trans('button.finish', [], 'CraueFormFlowBundle');

        if ($currentPratica->getEnte() == null && $this->container->getParameter('prefix') == null) {
            $this->selezioneComune($crawler, $nextButton, $ente, $form, $currentPratica, $erogatore);
        }
        $this->accettazioneIstruzioni($crawler, $nextButton, $form);
        $this->datiRichiedente($crawler, $nextButton, $fillData, $form);
        $this->datiIntestatario($crawler, $nextButton, $fillData, $form);
        $this->datiContatore($crawler, $nextButton, $fillData, $form);
        $this->datiLettura($crawler, $nextButton, $fillData, $form);
        $this->altreComunicazioni($crawler, $nextButton, $fillData, $form);

        $this->accettaGateway($crawler, $nextButton, $fillData, $form);

        $form = $crawler->selectButton($finishButton)->form();
        $this->client->submit($form);

        $this->em->persist($currentPratica);
        $this->em->refresh($currentPratica);

        $expettedStep = IscrizioneAsiloNidoFlow::STEP_ACCETTAZIONE_UTILIZZO_NIDO;
        if ($currentPratica->getEnte() != null && $this->container->getParameter('prefix') != null) {
            $expettedStep--;
        }

        $this->assertEquals($expettedStep, $currentPratica->getLastCompiledStep());

        $this->client->request('GET', $this->router->generate('home'));
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $currentPraticaResumeEditUrl = $this->router->generate('pratiche_compila', [
            'pratica' => $currentPraticaId,
            'instance' => $currentPratica->getInstanceId(),
            'step' => $currentPratica->getLastCompiledStep(),
            //MyPay respose parameters
            'idSession' => 'f61f7d3d-9efc-4662-8ad6-f2e0f941367f',
            'esito' => 'ERROR'
        ]);
        $crawler = $this->client->request('GET', $currentPraticaResumeEditUrl);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $currentStep = intval($crawler->filterXPath('//input[@name="flow_iscrizioneAsiloNido_step"]')->getNode(0)->getAttribute('value'));
        $this->assertEquals($currentPratica->getLastCompiledStep(), $currentStep);
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

    /**
     * @param $crawler
     * @param $nextButton
     * @param $fillData
     * @param $form
     */
    private function accettaGateway(&$crawler, $nextButton, &$fillData, &$form) {
        $form = $crawler->selectButton($nextButton)->form(array(
            'pratica_select_payment_gateway[payment_type]' => $this->gateway->getId(),
        ));
        $crawler = $this->client->submit($form);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
    }


}

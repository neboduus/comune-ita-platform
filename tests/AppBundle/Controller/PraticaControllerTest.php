<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\AsiloNido;
use AppBundle\Entity\Ente;
use AppBundle\Entity\IscrizioneAsiloNido;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use AppBundle\Form\IscrizioneAsiloNido\DatiRichiedenteType;
use AppBundle\Logging\LogConstants;
use AppBundle\Services\CPSUserProvider;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use Tests\AppBundle\Base\AbstractAppTestCase;

/**
 * Class PraticaControllerTest
 */
class PraticaControllerTest extends AbstractAppTestCase
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

        array_map('unlink', glob(__DIR__."/../../../web/pratiche/allegati/*"));

        $this->userProvider = $this->container->get('ocsdc.cps.userprovider');
        $this->em->getConnection()->executeQuery('DELETE FROM servizio_enti')->execute();
        $this->em->getConnection()->executeQuery('DELETE FROM ente_asili')->execute();
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
    public function testAsLoggedUserISeeAllMyPratiche()
    {
        $myUser = $this->createCPSUser(true);
        $this->createPratiche($myUser);

        $otherUser = $this->createCPSUser(true);
        $this->createPratiche($otherUser);

        $repo = $this->em->getRepository("AppBundle:Pratica");
        $myUserPraticheCountAfterInsert = count($repo->findByUser($myUser));

        $otherUserPraticheCountAfterInsert = count($repo->findByUser($otherUser));
        $this->assertGreaterThan(0, $otherUserPraticheCountAfterInsert);

        $crawler = $this->clientRequestAsCPSUser($myUser, 'GET', '/pratiche/');

        $renderedPraticheCount = $crawler->filterXPath('//*[@data-user="'.$myUser->getId().'"]')->count();
        $this->assertEquals($myUserPraticheCountAfterInsert, $renderedPraticheCount);

        $renderedOtherUserPraticheCount = $crawler->filterXPath('//*[@data-user="'.$otherUser->getId().'"]')->count();
        $this->assertEquals(0, $renderedOtherUserPraticheCount);
    }


    /**
     * @test
     */
    public function testAsLoggedUserISeeAllMyPraticheInCorrectOrder()
    {
        $user = $this->createCPSUser(true);
        $this->setupPraticheForUser($user);
        $expectedStatuses = $this->getExpectedPraticaStatuses();

        $crawler = $this->clientRequestAsCPSUser($user, 'GET', '/pratiche/');
        $renderedPraticheCount = $crawler->filterXPath('//*[@data-user="'.$user->getId().'"]')->count();
        $this->assertEquals(count($expectedStatuses), $renderedPraticheCount);

        //For now this logic is enough since sorting is based on actual constants values
        //it's quite brittle though
        rsort($expectedStatuses);
        for ($i = 0; $i < count($expectedStatuses); $i ++) {
            $statusPratica = $crawler->filterXPath('//*[@data-user="'.$user->getId().'"]')->getNode($i)->getAttribute('data-status');
            $this->assertEquals($statusPratica, $expectedStatuses[$i]);
        }
    }

    /**
     * @test
     */
    public function testANewPraticaIsPersistedWhenIStartTheFormApplicationAsLoggedUser()
    {
        $mockLogger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $mockLogger->expects($this->exactly(1))
                   ->method('info')
                   ->with(LogConstants::PRATICA_CREATED);

        $this->container->set('logger', $mockLogger);
        $user = $this->createCPSUser();

        $praticheRepository = $this->em->getRepository('AppBundle:Pratica');
        $tutteLePratiche = count($praticheRepository->findAll());
        $miePratiche = count($praticheRepository->findByUser($user));

        $servizio = new Servizio();
        $servizio->setName('Terzo servizio');
        $this->em->persist($servizio);
        $this->em->flush();

        $this->clientRequestAsCPSUser($user, 'GET', $this->router->generate(
            'pratiche_new',
            ['servizio' => $servizio->getSlug()]
        ));

        $tutteLePraticheNew = count($praticheRepository->findAll());
        $miePraticheNew = count($praticheRepository->findByUser($user));

        $this->assertEquals(++$tutteLePratiche, $tutteLePraticheNew);
        $this->assertEquals(++$miePratiche, $miePraticheNew);
    }

    /**
     * @test
     */
    public function testISeeistruzioniIscrizioneAsiloNidoApplicationFormWhenIStartTheFormAsLoggedUser()
    {
        $mockLogger = $this->getMockLogger();
        $mockLogger->expects($this->exactly(2))
                   ->method('info')
            ->with($this->callback(function ($subject) {
                $expectedArgs = [
                    LogConstants::PRATICA_CREATED,
                    LogConstants::PRATICA_COMPILING_STEP,
                ];

                return in_array($subject, $expectedArgs);
            }));

        static::$kernel->setKernelModifier(function (KernelInterface $kernel) use ($mockLogger) {
            $kernel->getContainer()->set('logger', $mockLogger);
        });
        $user = $this->createCPSUser();

        $ente1 = new Ente();
        $ente1->setName('Ente di prova');
        $this->em->persist($ente1);
        $this->em->flush();

        $ente2 = new Ente();
        $ente2->setName('Ente di prova 2');
        $this->em->persist($ente2);
        $this->em->flush();

        $servizio = new Servizio();
        $servizio->setName('Altro servizio')->setEnti([$ente1, $ente2]);
        $this->em->persist($servizio);
        $this->em->flush();

        $this->client->followRedirects();
        $this->clientRequestAsCPSUser($user, 'GET', $this->router->generate(
            'pratiche_new',
            ['servizio' => $servizio->getSlug()]
        ));

        $this->assertContains('iscrizione_asilo_nido_accettazione_istruzioni', $this->client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function testICanFillOutTheFormToEnrollMyChildInAsiloNidoAsLoggedUser()
    {
        $ente = $this->createEnteWithAsili();

        $servizio = $this->createServizioWithEnte($ente);

        $user = $this->createCPSUser();

        $this->clientRequestAsCPSUser($user, 'GET', $this->router->generate(
            'pratiche_new',
            ['servizio' => $servizio->getSlug()]
        ));
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
        $crawler = $this->client->followRedirect();

        $currentUriParts = explode('/', $this->client->getHistory()->current()->getUri());
        $currentPraticaId = array_pop($currentUriParts);
        $currentPratica  = $this->em->getRepository('AppBundle:IscrizioneAsiloNido')->find($currentPraticaId);
        $this->assertEquals(get_class($currentPratica), IscrizioneAsiloNido::class);

        $nextButton = $this->translator->trans('button.next', [], 'CraueFormFlowBundle');
        $finishButton = $this->translator->trans('button.finish', [], 'CraueFormFlowBundle');

        $this->accettazioneIstruzioni($crawler, $nextButton, $form);
        $this->selezioneComune($crawler, $nextButton, $ente, $form);
        $this->selezioneAsilo($ente, $crawler, $nextButton, $asiloSelected, $form);
        $this->terminiFruizione($asiloSelected, $crawler, $nextButton, $form);
        $this->datiRichiedente($crawler, $nextButton, $fillData, $form);
        $this->composizioneNucleoFamiliare($crawler, $nextButton, $form);
        $this->allegati($crawler, $numberOfExpectedAttachments, $form);

        $form = $crawler->selectButton($finishButton)->form();
        $this->client->submit($form);
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
        $this->client->followRedirect();

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
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
        $this->assertEquals($numberOfExpectedAttachments, count($allegati));
        foreach ($allegati as $allegato) {
            $this->assertTrue(file_exists($allegato->getFile()->getPathName()));
        }
    }

    /**
     * @test
     */
    public function testInputFieldsForMyCPSDataAreDisabled()
    {
        $ente = $this->createEnteWithAsili();

        $servizio = $this->createServizioWithEnte($ente);

        $user = $this->createCPSUserWithTelefonoAndEmail('1111','22@eee.55');

        $this->clientRequestAsCPSUser($user, 'GET', $this->router->generate(
            'pratiche_new',
            ['servizio' => $servizio->getSlug()]
        ));
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
        $crawler = $this->client->followRedirect();

        $currentUriParts = explode('/', $this->client->getHistory()->current()->getUri());
        $currentPraticaId = array_pop($currentUriParts);
        $currentPratica  = $this->em->getRepository('AppBundle:IscrizioneAsiloNido')->find($currentPraticaId);
        $this->assertEquals(get_class($currentPratica), IscrizioneAsiloNido::class);

        $nextButton = $this->translator->trans('button.next', [], 'CraueFormFlowBundle');
        $finishButton = $this->translator->trans('button.finish', [], 'CraueFormFlowBundle');

        // Accettazioni istruzioni
        $this->accettazioneIstruzioni($crawler, $nextButton, $form);

        // Selezione del comune
        $this->selezioneComune($crawler, $nextButton, $ente, $form);

        // Selezione del asilo
        $this->selezioneAsilo($ente, $crawler, $nextButton, $asiloSelected, $form);

        // Termini di fruizione della struttura
        $this->terminiFruizione($asiloSelected, $crawler, $nextButton, $form);

        // Dati richiedente
        $fillData = array();
        $crawler->filter('form[name="iscrizione_asilo_nido_richiedente"] input[type="text"]')
            ->each(function ($node, $i) use (&$fillData) {
                $name = $node->attr('name');
                $value = $node->attr('value');
                if (empty($value)) {
                    $fillData[$name] = 'test';
                }
            });
        $form = $crawler->selectButton($nextButton)->form();

        foreach (DatiRichiedenteType::CAMPI_RICHIEDENTE as $campo => $statoDisabledAtteso) {
            $field = $form->get('iscrizione_asilo_nido_richiedente['.$campo.']');
            switch ($campo) {
                case 'richiedente_telefono':
                    $statoDisabledAtteso = $user->getTelefono() == null ? false : true;
                    break;
                case 'richiedente_email':
                    $statoDisabledAtteso = $user->getEmail() == null ? false : true;
                    break;
                default:
                    break;
            }
            $this->assertEquals($statoDisabledAtteso, $field->isDisabled(), 'Il campo '.$field->getName().' doveva essere disabled: '.$statoDisabledAtteso);
        }

        $this->client->submit($form);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
    }

    /**
     * @return Ente
     */
    protected function createEnteWithAsili()
    {
        $asilo = new AsiloNido();
        $asilo->setName('Asilo nido Bimbi belli')
            ->setSchedaInformativa('Test');
        $this->em->persist($asilo);

        $asilo1 = new AsiloNido();
        $asilo1->setName('Asilo nido Bimbi buoni')
            ->setSchedaInformativa('Test');
        $this->em->persist($asilo1);

        $ente = new Ente();
        $ente->setName('Comune di Test')
            ->setAsili([$asilo, $asilo1]);
        $this->em->persist($ente);

        $this->em->flush();

        return $ente;
    }

    /**
     * @param $ente
     * @return Servizio
     */
    protected function createServizioWithEnte($ente)
    {
        $servizio = new Servizio();
        $servizio->setName('Iscrizione asilo nido')
            ->setEnti([$ente])
            ->setTestoIstruzioni("<strong>Tutto</strong> quello che volevi sapere sugli asili nido e non hai <em>mai</em> osato chiedere!");
        $this->em->persist($servizio);
        $this->em->flush();

        return $servizio;
    }

    /**
     * @param \DOMElement $node
     * @param int $currentIndex
     * @param int $count
     */
    protected function appendPrototypeDom(\DOMElement $node, $currentIndex = 0, $count = 1)
    {
        $prototypeHTML = $node->getAttribute('data-prototype');
        $accumulatedHtml = '';
        for ($i = 0; $i < $count; $i++) {
            $accumulatedHtml .= str_replace('__name__', $currentIndex + $i, $prototypeHTML);
        }
        $prototypeFragment = new \DOMDocument();
        $prototypeFragment->loadHTML($accumulatedHtml);
        foreach ($prototypeFragment->getElementsByTagName('body')->item(0)->childNodes as $prototypeNode) {
            $node->appendChild($node->ownerDocument->importNode($prototypeNode, true));
        }
    }

    /**
     * @param $crawler
     * @param $nextButton
     * @param $form
     */
    protected function accettazioneIstruzioni(&$crawler, $nextButton, &$form)
    {
        // Accettazioni istruzioni
        $form = $crawler->selectButton($nextButton)->form(array(
            'iscrizione_asilo_nido_accettazione_istruzioni[accetto_istruzioni]' => 1,
        ));
        $crawler = $this->client->submit($form);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
    }

    /**
     * @param $crawler
     * @param $nextButton
     * @param $ente
     * @param $form
     */
    protected function selezioneComune(&$crawler, $nextButton, $ente, &$form)
    {
        // Selezione del comune
        $form = $crawler->selectButton($nextButton)->form(array(
            'iscrizione_asilo_nido_seleziona_ente[ente]' => $ente->getId(),
        ));
        $crawler = $this->client->submit($form);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
    }

    /**
     * @param $ente
     * @param $crawler
     * @param $nextButton
     * @param $asiloSelected
     * @param $form
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
     * @param $asiloSelected
     * @param $crawler
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
     * @param $crawler
     * @param $nextButton
     * @param $fillData
     * @param $form
     */
    protected function datiRichiedente(&$crawler, $nextButton, &$fillData, &$form)
    {
        // Dati richiedente
        $fillData = array();
        $crawler->filter('form[name="iscrizione_asilo_nido_richiedente"] input[type="text"]')
            ->each(function ($node, $i) use (&$fillData) {
                $name = $node->attr('name');
                $value = $node->attr('value');
                if (empty($value)) {
                    $fillData[$name] = 'test';
                }
            });
        $form = $crawler->selectButton($nextButton)->form($fillData);
        $crawler = $this->client->submit($form);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
    }

    /**
     * @param $crawler
     * @param $nextButton
     * @param $form
     */
    protected function composizioneNucleoFamiliare(&$crawler, $nextButton, &$form)
    {
        /** FIXME: Questo test non fa submit di elementi nuovi, né li verifica
         *  bisogna adattarlo sulla falsariga del test per l'upload allegati
         */
        // Composizione del nucleo familiare
        $fillData = array();
        $crawler->filter('form[name="iscrizione_asilo_nido_nucleo_familiare"] input[type="text"]')
            ->each(function ($node, $i) use (&$fillData) {
                $name = $node->attr('name');
                $value = $node->attr('value');
                if (empty($value)) {
                    $fillData[$name] = 'test';
                }
            });
        $form = $crawler->selectButton($nextButton)->form($fillData);
        $crawler = $this->client->submit($form);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
    }

    /**
     * @param $crawler
     * @param $numberOfExpectedAttachments
     * @param $form
     */
    protected function allegati(&$crawler, &$numberOfExpectedAttachments, &$form)
    {
        //allegati
        $numberOfExpectedAttachments = 2;
        $formCrawler = $crawler->filter('form[name="iscrizione_asilo_nido_allegati"]');
        $this->appendPrototypeDom($crawler->filter('.allegati')->getNode(0), 0, $numberOfExpectedAttachments);
        $form = $formCrawler->form();
        $values = $form->getValues();
        $values['iscrizione_asilo_nido_allegati[allegati][0][description]'] = 'pippo';
        $values['iscrizione_asilo_nido_allegati[allegati][0][file][file]'] = new UploadedFile(
            __DIR__.'/../Assets/lenovo-yoga-xp1.pdf',
            'lenovo-yoga-xp1.pdf',
            'application/postscript',
            filesize(__DIR__.'/../Assets/lenovo-yoga-xp1.pdf')
        );
        $values['iscrizione_asilo_nido_allegati[allegati][1][description]'] = 'pippo';
        $values['iscrizione_asilo_nido_allegati[allegati][1][file][file]'] = new UploadedFile(
            __DIR__.'/../Assets/lenovo-yoga-xp1.pdf',
            'lenovo-yoga-xp1.pdf',
            'application/postscript',
            filesize(__DIR__.'/../Assets/lenovo-yoga-xp1.pdf')
        );

        $form->setValues($values);
        $crawler = $this->client->submit($form);
    }
}

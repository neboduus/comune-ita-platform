<?php

namespace Tests\AppBundle\Base;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\AsiloNido;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\CPSUser as User;
use AppBundle\Entity\Ente;
use AppBundle\Entity\IscrizioneAsiloNido as Pratica;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Servizio;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class AbstractAppTestCase
 *
 * @package Tests\AppBundle\Base
 */
abstract class AbstractAppTestCase extends WebTestCase
{
    const OTHER_USER_ALLEGATO_DESCRIPTION = 'other';
    const CURRENT_USER_ALLEGATO_DESCRIPTION_PREFIX = 'description_';

    /**
     * @var \AppTestKernel
     */
    protected static $kernel;

    /**
     *
     * @var Client
     */
    protected $client;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->client = static::createClient();
        $this->container = $this->client->getContainer();
        $this->em = $this->container->get('doctrine')->getManager();
        $this->router = $this->container->get('router');
        $this->translator = $this->container->get('translator');
        parent::setUp();
    }

    protected function cleanDb($entityString)
    {
        $this->em->createQuery('DELETE FROM ' . $entityString)->execute();
    }

    protected function getCPSUserData()
    {
        $random = rand(0, time());

        return [
            "codiceFiscale" => 'ppippi77t05g224f' . $random,
            "capDomicilio" => '371378',
            "capResidenza" => '38127',
            "cellulare" => '123456789',
            "cittaDomicilio" => 'Verona',
            "cittaResidenza" => 'Trento',
            "cognome" => 'Pippucci' . $random,
            "dataNascita" => '04/01/1973',
            "emailAddress" => 'pippo@pippucci.com' . $random,
            "emailAddressPersonale" => null,
            "indirizzoDomicilio" => 'via Leonardo da vinci 17',
            "indirizzoResidenza" => 'via Marsala 13',
            "luogoNascita" => 'Verona',
            "nome" => 'Pippo' . $random,
            "provinciaDomicilio" => 'VR',
            "provinciaNascita" => 'VR',
            "provinciaResidenza" => 'TN',
            "sesso" => 'M',
            "statoDomicilio" => 'IT',
            "statoNascita" => 'IT',
            "statoResidenza" => 'IT',
            "telefono" => null,
            "titolo" => null,
            "x509certificate_issuerdn" => null,
            "x509certificate_subjectdn" => null,
            "x509certificate_base64" => null,
        ];
    }

    protected function createCPSUserWithTelefonoAndEmail($telefono, $email)
    {
        return $this->createCPSUser(true, $telefono, $email);
    }

    protected function createCPSUser($termAccepted = true, $telefono = null, $email = null)
    {
        $user = $this->container->get('ocsdc.cps.userprovider')->provideUser($this->getCPSUserData());
        if ($termAccepted) {
            $user->setTermsAccepted(true);
        }

        if ($telefono != null) {
            $user->setTelefono($telefono);
        }
        if ($email != null) {
            $user->setEmail($email);
        }
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * Attempts to guess the kernel location.
     *
     * When the Kernel is located, the file is required.
     *
     * @return string The Kernel class name
     *
     * @throws \RuntimeException
     */
    protected static function getKernelClass()
    {
        $dir = isset( $_SERVER['KERNEL_DIR'] ) ? $_SERVER['KERNEL_DIR'] : static::getPhpUnitXmlDir();
        $dir = __DIR__ . '/../../../' . $dir;
        $finder = new Finder();
        $finder->name('*TestKernel.php')->depth(0)->in($dir);
        $results = iterator_to_array($finder);
        if (!count($results)) {
            throw new \RuntimeException('Either set KERNEL_DIR in your phpunit.xml according to https://symfony.com/doc/current/book/testing.html#your-first-functional-test or override the WebTestCase::createKernel() method.');
        }


        $file = current($results);
        $class = $file->getBasename('.php');

        require_once $file;

        return $class;
    }

    protected function clientRequestAsCPSUser(
        User $user,
        $method,
        $uri,
        array $parameters = array(),
        array $files = array(),
        array $server = array(),
        $content = null,
        $changeHistory = true
    ) {
        $server += ['REDIRECT_shibb_pat_attribute_codicefiscale' => $user->getCodiceFiscale()];

        return $this->client->request($method, $uri, $parameters, $files, $server, $content, $changeHistory);
    }

    protected function getMockLogger()
    {
        return $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @param User $user
     * @param bool $howMany
     *
     * @return array
     */
    protected function createPratiche(User $user, $howMany = false)
    {
        $pratiche = array();
        if (!$howMany) {
            $howMany = rand(1, 10);
        }

        for ($i = 0; $i < $howMany; $i++) {
            $pratiche [] = $this->createPratica($user);
        }

        return $pratiche;
    }


    /**
     * @param CPSUser $user
     * @param OperatoreUser|null $operatore
     * @param null $status
     * @param Ente|null $ente
     * @param Servizio|null $servizio
     *
     * @return Pratica
     */
    protected function createPratica(
        CPSUser $user,
        OperatoreUser $operatore = null,
        $status = null,
        Ente $ente = null,
        Servizio $servizio = null
    ) {
        if ($servizio == null) {
            $servizio = $this->createServizioWithAssociatedEnti($this->createEnti());
        }

        $pratica = new Pratica();
        $pratica->setUser($user);
        $pratica->setServizio($servizio);
        if ($operatore) {
            $pratica->setOperatore($operatore);
        }
        if ($ente) {
            $pratica->setEnte($ente);
        }

        if ($status !== null) {
            $pratica->setStatus($status);
        } else {
            $pratica->setStatus(Pratica::STATUS_DRAFT);
        }

        $this->em->persist($pratica);
        $this->em->flush();
        $this->em->refresh($pratica);

        return $pratica;
    }

    /**
     * @param $user
     */
    protected function setupPraticheForUser(CPSUser $user)
    {
        $expectedStatuses = $this->getExpectedPraticaStatuses();
        foreach ($expectedStatuses as $status) {
            $this->createPratica($user, null, $status);
        }
    }

    /**
     * @param CPSUser $user
     * @param OperatoreUser $operatore
     * @param $status
     *
     * @return Pratica|null
     */
    protected function setupPraticheForUserWithOperatoreAndStatus(
        CPSUser $user,
        OperatoreUser $operatore = null,
        $status = null
    ) {
        return $this->createPratica($user, $operatore, $status);
    }

    /**
     * @param CPSUser $user
     * @param Ente $ente
     * @param $status
     *
     * @return Pratica|null
     */
    protected function setupPraticheForUserWithEnteAndStatus(CPSUser $user, Ente $ente = null, $status = null)
    {
        return $this->createPratica($user, null, $status, $ente);
    }

    /**
     * @return array
     */
    protected function getExpectedPraticaStatuses()
    {
        $expectedStatuses = [
            Pratica::STATUS_PENDING,
            Pratica::STATUS_REGISTERED,
            Pratica::STATUS_COMPLETE,
            Pratica::STATUS_SUBMITTED,
            Pratica::STATUS_DRAFT,
            Pratica::STATUS_CANCELLED,
        ];

        shuffle($expectedStatuses);

        return $expectedStatuses;
    }

    /**
     * @param Ente[] $enti
     * @param string $name
     * @param string $praticaFCQN
     * @param string $praticaFlowServiceName
     *
     * @return Servizio
     */
    protected function createServizioWithAssociatedEnti(
        $enti,
        $name = 'Servizio test pratiche',
        $praticaFCQN = '\AppBundle\Entity\IscrizioneAsiloNido',
        $praticaFlowServiceName = 'ocsdc.form.flow.asilonido'
    ) {
        $servizio = new Servizio();
        $servizio
            ->setName($name.'_'.md5(rand(0, 100).microtime()))
            ->setEnti($enti)
            ->setDescription('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer ultricies eros eu dignissim bibendum. Praesent tortor nibh, sodales vel ante quis, ultrices consequat ipsum. Praesent vestibulum vel eros nec consectetur. Phasellus et eros vestibulum, ultrices nisl nec, pharetra velit. Donec in ex fermentum, accumsan eros ac, convallis nulla. Donec ut suscipit purus, eget dignissim odio. Duis a congue felis.')
            ->setArea('Test area')
            ->setPraticaFCQN($praticaFCQN)
            ->setPraticaFlowServiceName($praticaFlowServiceName);
        $this->em->persist($servizio);
        $this->em->flush();

        return $servizio;
    }

    /**
     * @return Collection
     */
    protected function createEnti()
    {
        $repo = $this->em->getRepository('AppBundle:Ente');
        $ente1 = $repo->findOneByCodiceMeccanografico('L378');
        if(!$ente1) {
            $ente1 = new Ente();
            $ente1->setName('Ente di prova');
            $ente1->setCodiceMeccanografico('L378');
            $this->em->persist($ente1);
        }

        $ente2 = $repo->findOneByCodiceMeccanografico('L781');
        if(!$ente2) {
            $ente2 = new Ente();
            $ente2->setName('Ente di prova 2');
            $ente2->setCodiceMeccanografico('L781');
            $this->em->persist($ente2);
        }
        $this->em->flush();

        return new ArrayCollection(array($ente1, $ente2));
    }

    /**
     * @param $username
     * @param $password
     * @param Ente $ente
     *
     * @return OperatoreUser
     */
    protected function createOperatoreUser($username, $password, Ente $ente = null)
    {
        $um = $this->container->get('fos_user.user_manager');
        $user = new OperatoreUser();
        $user->setUsername($username)
             ->setPlainPassword($password)
             ->setEmail(md5(rand(0, 1000) . microtime()) . 'some@fake.email')
             ->setNome('a')
             ->setCognome('b')
             ->setEnabled(true);

        if ($ente) {
            $user->setEnte($ente);
        }

        $um->updateUser($user);

        return $user;
    }

    protected function setupSwiftmailerMock($recipients = [])
    {
        //swiftmailer.mailer.default
        $mock = $this->getMockBuilder(\Swift_Mailer::class)
                     ->disableOriginalConstructor()
                     ->getMock();

        $mock->expects($this->exactly(count($recipients)))
             ->method('send')
             ->willReturn(count($recipients));

        return $mock;
    }

    protected function setupMockedLogger($expectedArgs)
    {
        $mockLogger = $this->getMockLogger();
        $mockLogger->expects($this->exactly(1))
                   ->method('info')
                   ->with($this->callback(function ($subject) use ($expectedArgs) {
                       return in_array($subject, $expectedArgs);
                   }));

        static::$kernel->setKernelModifier(function (KernelInterface $kernel) use ($mockLogger) {
            $kernel->getContainer()->set('logger', $mockLogger);
        });
    }


    /**
     * @return Ente
     */
    protected function createEnteWithAsili($codiceMeccanografico = 'L781')
    {
        $asilo = new AsiloNido();
        $asilo->setName('Asilo nido Bimbi belli')
              ->setSchedaInformativa('Test')
              ->setOrari([
                  'orario intero dalle 8:00 alle 16:00',
                  'orario ridotto mattutino dalle 8:00 alle 13:00',
                  'orario prolungato dalle 8:00 alle 19:00',
              ]);
        $this->em->persist($asilo);

        $asilo1 = new AsiloNido();
        $asilo1->setName('Asilo nido Bimbi buoni')
               ->setSchedaInformativa('Test')
               ->setOrari([
                   'orario intero dalle 8:00 alle 16:00',
                   'orario ridotto mattutino dalle 8:00 alle 13:00',
                   'orario prolungato dalle 8:00 alle 19:00',
               ]);
        $this->em->persist($asilo1);

        $ente = new Ente();
        $ente->setName('Comune di Test')
             ->setCodiceMeccanografico($codiceMeccanografico)
             ->setAsili([$asilo, $asilo1]);
        $this->em->persist($ente);

        $this->em->flush();

        return $ente;
    }

    /**
     * @param Ente $ente
     * @param string $slug
     * @param string $fqcn
     * @param string $flow
     *
     * @return Servizio
     */
    protected function createServizioWithEnte($ente, $slug, $fqcn, $flow)
    {
        //'Iscrizione asilo nido'
        $servizio = $this->createServizioWithAssociatedEnti([$ente], $slug, $fqcn, $flow);
        $servizio->setTestoIstruzioni('<strong>Tutto</strong> quello che volevi sapere su ' . $slug . ' e non hai <em>mai</em> osato chiedere!');
        $this->em->persist($servizio);
        $this->em->flush();

        return $servizio;
    }

    /**
     * @param Crawler $crawler
     * @param $nextButton
     * @param $form
     */
    protected function accettazioneIstruzioni(&$crawler, $nextButton, &$form)
    {
        // Accettazioni istruzioni
        $form = $crawler->selectButton($nextButton)->form(array(
            'pratica_accettazione_istruzioni[accetto_istruzioni]' => 1,
        ));
        $crawler = $this->client->submit($form);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
    }

    /**
     * @param Crawler $crawler
     * @param $nextButton
     * @param $form
     */
    protected function nextStep(&$crawler, $nextButton, &$form)
    {
        $form = $crawler->selectButton($nextButton)->form();
        $crawler = $this->client->submit($form);
    }

    /**
     * @param Crawler $crawler
     * @param $nextButton
     * @param Ente $ente
     * @param $form
     */
    protected function selezioneComune(&$crawler, $nextButton, $ente, &$form)
    {
        // Selezione del comune
        $form = $crawler->selectButton($nextButton)->form(array(
            'pratica_seleziona_ente[ente]' => $ente->getId(),
        ));
        $crawler = $this->client->submit($form);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
    }

    /**
     * @param Crawler $crawler
     * @param $class
     */
    protected function gobackInFlow(&$crawler, $class)
    {
        $accumulatedHtml = '<input type="hidden" name="flow_iscrizioneAsiloNido_transition" value="back">';
        $prototypeFragment = new \DOMDocument();
        $prototypeFragment->loadHTML($accumulatedHtml);
        $node = $crawler->filter($class)->getNode(0);
        foreach ($prototypeFragment->getElementsByTagName('body')->item(0)->childNodes as $prototypeNode) {
            $node->appendChild($node->ownerDocument->importNode($prototypeNode, true));
        }
    }

    /**
     * @param Crawler $crawler
     * @param $nextButton
     * @param $form
     * @param $offset
     * @param $amount
     */
    protected function composizioneNucleoFamiliare(&$crawler, $nextButton, &$form, $offset, $amount)
    {
        /** FIXME: Questo test non fa submit di elementi nuovi, né li verifica
         *  bisogna adattarlo sulla falsariga del test per l'upload allegati
         */
        $formCrawler = $crawler->selectButton($nextButton);
        $this->appendPrototypeDom($crawler->filter('.nucleo_familiare')->getNode(0), $offset, $amount);

        $form = $formCrawler->form();
        $values = $form->getValues();

        for ($i = $offset; $i < $offset + $amount; $i++) {
            $values['nucleo_familiare[nucleo_familiare][' . $i . '][nome]'] = $i . 'pippo' . md5($i . time());
            $values['nucleo_familiare[nucleo_familiare][' . $i . '][cognome]'] = $i . 'pippo' . md5($i . time());
            $values['nucleo_familiare[nucleo_familiare][' . $i . '][codiceFiscale]'] = $i . 'pippo' . md5($i . time());
            $values['nucleo_familiare[nucleo_familiare][' . $i . '][rapportoParentela]'] = $i . 'pippo' . md5($i . time());
        }

        $form->setValues($values);
        $crawler = $this->client->submit($form);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
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
     * @param Crawler $crawler
     * @param $button
     * @param $form
     * @param Allegato[] $allegati
     */
    protected function allegati(&$crawler, $button, &$form, $allegati)
    {
        //TODO: test that I cannot see other people's allegati!
        //check that we see only our allegati

        //all but the first;
        $allegatiSelezionati = [];
        for ($i = 1; $i < count($allegati); $i++) {
            $allegatiSelezionati[] = $allegati[$i]->getId();
        }

        $form = $crawler->selectButton($button)->form();
        $form->disableValidation();
        $values = $form->getPhpValues();
        $values['pratica_allegati']['allegati'] = $allegatiSelezionati;
        $form->setValues($values);
        $crawler = $this->client->submit($form);

        $this->em->persist($allegati[0]);
        $this->em->refresh($allegati[0]);
        $this->assertEquals(0, $allegati[0]->getPratiche()->count());
//        for ($i = 1; $i < count($allegati); $i++) {
//            $this->assertEquals(1, $allegati[$i]->getPratiche()->count());
//        }
    }

    /**
     * @param Crawler $crawler
     * @param $nextButton
     * @param $fillData
     * @param $form
     */
    protected function datiRichiedente(&$crawler, $nextButton, &$fillData, &$form)
    {
        // Dati richiedente
        $fillData = array();
        $crawler->filter('form[name="pratica_richiedente"] input[type="text"]')
                ->each(function ($node, $i) use (&$fillData) {
                    self::fillFormInputWithDummyText($node, $i, $fillData);
                });
        $form = $crawler->selectButton($nextButton)->form($fillData);
        $crawler = $this->client->submit($form);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
    }

    /**
     * @param $numberOfExpectedAttachments
     * @param CPSUser $user
     *
     * @return Allegato[]
     */
    protected function setupNeededAllegatiForAllInvolvedUsers($numberOfExpectedAttachments, CPSUser $user)
    {
        $allegati = [];
        for ($i = 0; $i < $numberOfExpectedAttachments; $i++) {
            $allegato = new Allegato();
            $allegato->setOwner($user);
            $allegato->setDescription(self::CURRENT_USER_ALLEGATO_DESCRIPTION_PREFIX . $i);
            $allegato->setFilename('somefile.txt');
            $allegato->setOriginalFilename('somefile.txt');
            $this->em->persist($allegato);
            $allegati[] = $allegato;
        }

        $otherUser = $this->createCPSUser(true);
        $allegato = new Allegato();
        $allegato->setOwner($otherUser);
        $allegato->setDescription(self::OTHER_USER_ALLEGATO_DESCRIPTION);
        $allegato->setFilename('somefile.txt');
        $allegato->setOriginalFilename('somefile.txt');
        $this->em->persist($allegato);

        $this->em->flush();

        return $allegati;
    }

    protected static function fillFormInputWithDummyText(Crawler $node, $i, &$fillData, $dummyText = 'test')
    {
        $type = $node->attr('type');
        if ($type == 'number'){
            $dummyText = rand(0, time());
        }
        $name = $node->attr('name');
        $value = $node->attr('value');
        if (empty($value)){
            $fillData[$name] = $dummyText;
        }
    }

    /**
     * @param Crawler $crawler
     * @param $nextButton
     * @param $form
     */
    protected function datiBambino(&$crawler, $nextButton, &$form)
    {
        $form = $crawler->selectButton($nextButton)->form(array(
            'iscrizione_asilo_nido_bambino[bambino_nome]' => 'Ciccio',
            'iscrizione_asilo_nido_bambino[bambino_cognome]' => 'Balena',
            'iscrizione_asilo_nido_bambino[bambino_luogo_nascita]' => 'Fantasilandia',
            'iscrizione_asilo_nido_bambino[bambino_data_nascita]' => '01-09-2016',

        ));
        $crawler = $this->client->submit($form);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
    }
}

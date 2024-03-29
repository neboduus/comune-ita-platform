<?php

namespace Tests\Base;

use App\Entity\Allegato;
use App\Entity\AllegatoOperatore;
use App\Entity\AsiloNido;
use App\Entity\CertificatoNascita;
use App\Entity\CPSUser;
use App\Entity\CPSUser as User;
use App\Entity\Ente;
use App\Entity\Erogatore;
use App\Entity\IscrizioneAsiloNido as Pratica;
use App\Entity\OperatoreUser;
use App\Entity\RispostaOperatore;
use App\Entity\SciaPraticaEdilizia;
use App\Entity\Servizio;
use App\Entity\Categoria;
use App\Entity\TerminiUtilizzo;
use App\Mapper\Giscom\SciaPraticaEdilizia\ElencoSoggettiAventiTitolo;
use App\Mapper\Giscom\File as GiscomFile;
use App\Mapper\Giscom\FileCollection as GiscomFileCollection;
use App\Mapper\Giscom\SciaPraticaEdilizia as MappedPraticaEdilizia;
use App\Security\AbstractAuthenticator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use EightPoints\Bundle\GuzzleBundle\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\TranslatorInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * Class AbstractAppTestCase
 *
 * @package Tests\App\Base
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
   * @var Router
   */
  protected $router;

  /**
   * @var TranslatorInterface
   */
  protected $translator;

  protected $spy;

  protected $baseUri;


  /**
   * @inheritdoc
   */
  public function setUp()
  {
    $this->client = static::createClient();
    $this->em = self::$container->get('doctrine')->getManager();
    $this->router = self::$container->get('router');
    $this->translator = self::$container->get('translator');
    $this->baseUri = '/'.static::$kernel->getIdentifier();
    parent::setUp();
  }

  protected static function createClient(array $options = array(), array $server = array())
  {
    static::bootKernel($options);

    $client = new InstanceAwareClient(static::$kernel);
    $client->setServerParameters($server);

    return $client;
  }

  protected function cleanDb($entityString)
  {
    $this->em->createQuery('DELETE FROM '.$entityString)->execute();
  }

  protected function getCPSUserData()
  {
    return array_merge($this->getCPSUserBaseData(), $this->getCPSUserExtraData());
  }

  protected function getCPSUserBaseData()
  {
    $random = rand(0, time());

    return [
      "codiceFiscale" => 'ppippi77t05g224f'.$random,
      "cognome" => 'Pippucci'.$random,
      "nome" => 'Pippo'.$random,
    ];
  }

  protected function getCPSUserExtraData()
  {
    $random = rand(0, time());

    return [
      "capDomicilio" => '371378',
      "capResidenza" => '38127',
      "cellulare" => '123456789',
      "cittaDomicilio" => 'Verona',
      "cittaResidenza" => 'Trento',
      "dataNascita" => '04/01/1973',
      "emailAddress" => 'pippo@pippucci.com'.$random,
      "emailAddressPersonale" => null,
      "indirizzoDomicilio" => 'via Leonardo da vinci 17',
      "indirizzoResidenza" => 'via Marsala 13',
      "luogoNascita" => 'Verona',
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

  protected function createCPSUser($termAccepted = true, $profileData = true, $additionalRole = null)
  {
    $data = $profileData ? $this->getCPSUserData() : $this->getCPSUserBaseData();
    $user = self::$container->get('ocsdc.cps.userprovider')->provideUser($data);
    if ($termAccepted) {
      $termsRepo = $this->em->getRepository('App:TerminiUtilizzo');
      $terms = $termsRepo->findByMandatory(true);
      foreach ($terms as $term) {
        $user->addTermsAcceptance($term);
      }
    }

    if ($additionalRole) {
      $user->addRole($additionalRole);
    }

    $this->em->persist($user);
    $this->em->flush();
    $this->em->refresh($user);

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
    $dir = isset($_SERVER['KERNEL_DIR']) ? $_SERVER['KERNEL_DIR'] : static::getPhpUnitXmlDir();
    $dir = __DIR__.'/../../../'.$dir;
    $finder = new Finder();
    $finder->name('*TestKernel.php')->depth(0)->in($dir);
    $results = iterator_to_array($finder);
    if (!count($results)) {
      throw new \RuntimeException(
        'Either set KERNEL_DIR in your phpunit.xml according to https://symfony.com/doc/current/book/testing.html#your-first-functional-test or override the WebTestCase::createKernel() method.'
      );
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

    $server += ['shibb_pat_attribute_codicefiscale' => $user->getCodiceFiscale()];
    $server += ['shibb_pat_attribute_spid_code' => '123456789'];
    $server += ['shibb_pat_attribute_x509certificate_issuerdn' => '123456789'];
    $server += ['shibb_pat_attribute_x509certificate_subjectdn' => '123456789'];
    $server += ['shibb_pat_attribute_x509certificate_base64' => '123456789'];
    $server += ['shibb_Shib-Session-ID' => 'abc123abc123abc123abc123abc123abc123abc123'];
    $server += ['shibb_Shib-Session-Index' => 'abc123abc123abc123abc123abc123abc123abc123'];
    $server += ['shibb_Shib-Authentication-Instant' => '2000-01-01T00-00Z'];

    return $this->client->request($method, $uri, $parameters, $files, $server, $content, $changeHistory);
  }

  /**
   * @return LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
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
   * @param Erogatore|null $erogatore
   * @param Servizio|null $servizio
   *
   * @return Pratica
   */
  protected function createPratica(
    CPSUser $user,
    OperatoreUser $operatore = null,
    $status = null,
    Erogatore $erogatore = null,
    Servizio $servizio = null,
    $year = null
  ) {
    if (!$erogatore) {
      $erogatore = $this->createErogatoreWithEnti(
        $this->createEnti()
      );
    }
    if ($servizio == null) {
      $servizio = $this->createServizioWithAssociatedErogatori(
        [$erogatore]
      );
    }

    $praticaClass = $servizio->getPraticaFCQN();
    /** @var Pratica $pratica */
    $pratica = new $praticaClass();
    $pratica->setUser($user);
    $pratica->setServizio($servizio);

    if ($operatore) {
      /** @var OperatoreUser $operatore */
      $operatore = $this->em->merge($operatore);
      $pratica->setOperatore($operatore);
    }

    $pratica->setErogatore($erogatore);
    $pratica->setEnte($erogatore->getEnti()[0]);

    if ($status !== null) {
      $pratica->setStatus($status);
      if ($status > Pratica::STATUS_SUBMITTED) {
        $pratica->setNumeroProtocollo('test');
        $pratica->setIdDocumentoProtocollo('test');
      }
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
  protected function setupPraticheForUser(CPSUser $user, $alsoCreateRelatedScia = false)
  {
    $expectedStatuses = $this->getExpectedPraticaStatuses();
    foreach ($expectedStatuses as $status) {
      $this->createPratica($user, null, $status);
    }

    if ($alsoCreateRelatedScia) {
      $this->setupPraticaScia([$user->getCodiceFiscale()]);
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
   * @param Erogatore $erogatore
   * @param $status
   *
   * @return Pratica|null
   */
  protected function setupPraticheForUserWithErogatoreAndStatus(
    CPSUser $user,
    Erogatore $erogatore = null,
    $status = null
  ) {
    return $this->createPratica($user, null, $status, $erogatore);
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
   * @param Erogatore[] $erogatori
   * @param string $name
   * @param string $praticaFCQN
   * @param string $praticaFlowServiceName
   * @param string $praticaFlowOperatoreServiceName
   *
   * @return Servizio
   */
  protected function createServizioWithAssociatedErogatori(
    $erogatori,
    $name = 'Servizio test pratiche',
    $praticaFCQN = '\App\Entity\IscrizioneAsiloNido',
    $praticaFlowServiceName = 'ocsdc.form.flow.asilonido',
    $praticaFlowOperatoreServiceName = ''
  ) {


    $area = new Categoria();
    $area
      ->setName('Nome categoria di Prova')
      ->setDescription('Descrizione categoria di Prova')
      ->setTreeId(1)
      ->setTreeParentId(0);
    $this->em->persist($area);
    $this->em->flush();

    $servizio = new Servizio();
    $servizio
      ->setName($name.'_'.md5(rand(0, 100).microtime()))
      ->setErogatori($erogatori)
      ->setDescription(
        'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer ultricies eros eu dignissim bibendum. Praesent tortor nibh, sodales vel ante quis, ultrices consequat ipsum. Praesent vestibulum vel eros nec consectetur. Phasellus et eros vestibulum, ultrices nisl nec, pharetra velit. Donec in ex fermentum, accumsan eros ac, convallis nulla. Donec ut suscipit purus, eget dignissim odio. Duis a congue felis.'
      )
      ->setArea($area)
      ->setPraticaFCQN($praticaFCQN)
      ->setPraticaFlowServiceName($praticaFlowServiceName)
      ->setPraticaFlowOperatoreServiceName($praticaFlowOperatoreServiceName);

    $this->em->persist($servizio);

    foreach ($erogatori as $erogatore) {
      $this->em->persist($erogatore);
      foreach ($erogatore->getEnti() as $ente) {
        if (\Doctrine\ORM\UnitOfWork::STATE_MANAGED !== $this->em->getUnitOfWork()->getEntityState($ente)) {
          //                    $this->em->persist($ente);
        }

      }
    }

    $this->em->flush();

    return $servizio;
  }

  protected function createErogatoreWithEnti($enti)
  {
    if (empty($enti)) {
      $enti = $this->createEnti();
    }

    $erogatore = new Erogatore();
    $erogatore->setName('Erogatore '.time());
    foreach ($enti as $ente) {
      $erogatore->addEnte($ente);
    }
    $this->em->persist($erogatore);
    $this->em->flush();

    return $erogatore;
  }

  /**
   * @return Collection
   */
  protected function createEnti()
  {
    $repo = $this->em->getRepository('App:Ente');
    $ente1 = $repo->findOneByCodiceMeccanografico('L378');
    if (!$ente1) {
      $ente1 = new Ente();
      // Modifico in comune di tre ville per avere uniformità tra enti creati ed identificatore del test
      $ente1->setName('Comune di Tre Ville');
      $ente1->setCodiceMeccanografico('L378');
      $ente1->setCodiceAmministrativo('L378');
      $ente1->setSiteUrl('http://example.com');
      $this->em->persist($ente1);
    }

    $ente2 = $repo->findOneByCodiceMeccanografico('L781');
    if (!$ente2) {
      $ente2 = new Ente();
      $ente2->setName('Ente di prova 2');
      $ente2->setCodiceMeccanografico('L781');
      $ente2->setCodiceAmministrativo('L781');
      $ente2->setSiteUrl('http://example.com');
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
  protected function createOperatoreUser($username, $password, Ente $ente = null, $serviziAbilitati = null)
  {
    $um = self::$container->get('fos_user.user_manager');

    if (!$serviziAbilitati) {
      $serviziAbilitati = new ArrayCollection();
    }

    $operatore = new OperatoreUser();
    $operatore->setUsername($username)
      ->setPlainPassword($password)
      ->setEmail(md5(rand(0, 1000).microtime()).'some@fake.email')
      ->setNome('a')
      ->setCognome('b')
      ->setEnabled(true)
      ->setServiziAbilitati($serviziAbilitati);

    if ($ente) {
      $operatore->setEnte($ente);
    }

    $um->updateUser($operatore);
    $this->em->refresh($operatore);
    if ($ente) {
      $operatore->setEnte($ente);
    }

    return $operatore;
  }

  protected function setupSwiftmailerMock($recipients = [])
  {
    //swiftmailer.mailer.default
    $mock = $this->getMockBuilder(\Swift_Mailer::class)
      ->disableOriginalConstructor()
      ->getMock();

    $mock->expects($this->spy = $this->exactly(count($recipients)))
      ->method('send')
      ->willReturn(count($recipients));

    return $mock;
  }

  protected function setupMockedLogger($expectedArgs, $loggerMethod = 'info')
  {
    $mockLogger = $this->getMockLogger();
    $mockLogger->expects($this->exactly(count($expectedArgs)))
      ->method($loggerMethod)
      ->with(
        $this->callback(function ($subject) use ($expectedArgs) {
          return in_array($subject, $expectedArgs);
        })
      );

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
    $ente->setName('Comune di Tre Ville')
      ->setCodiceMeccanografico($codiceMeccanografico)
      ->setCodiceAmministrativo($codiceMeccanografico)
      ->setAsili([$asilo, $asilo1]);
    $this->em->persist($ente);

    $this->em->flush();

    return $ente;
  }

  /**
   * @param Erogatore $erogatore
   * @param string $slug
   * @param string $fqcn
   * @param string $flow
   *
   * @return Servizio
   */
  protected function createServizioWithErogatore(Erogatore $erogatore, $slug, $fqcn, $flow)
  {
    //'Iscrizione asilo nido'
    $servizio = $this->createServizioWithAssociatedErogatori([$erogatore], $slug, $fqcn, $flow);
    $servizio->setHowto(
      '<strong>Tutto</strong> quello che volevi sapere su '.$slug.' e non hai <em>mai</em> osato chiedere!'
    );

    $this->em->persist($servizio);
    $this->em->flush();
    $this->em->refresh($servizio);

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
  protected function selezioneComune(&$crawler, $nextButton, $ente, &$form, $pratica, $erogatore)
  {
    // Selezione del comune
    $form = $crawler->selectButton($nextButton)->form(array(
      'pratica_seleziona_ente[ente]' => $ente->getId(),
    ));
    $crawler = $this->client->submit($form);
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");

    $this->em->refresh($pratica);
    $persistedErogatore = $pratica->getErogatore();
    $this->assertEquals($erogatore->getId(), $persistedErogatore->getId());
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
   * @param string $filter
   * @param string $prefix
   */
  protected function composizioneNucleoFamiliare(
    &$crawler,
    $nextButton,
    &$form,
    $offset,
    $amount,
    $filter = '.nucleo_familiare',
    $prefix = 'nucleo_familiare[nucleo_familiare]'
  ) {
    /** FIXME: Questo test non fa submit di elementi nuovi, né li verifica
     *  bisogna adattarlo sulla falsariga del test per l'upload allegati
     */
    $formCrawler = $crawler->selectButton($nextButton);
    $this->appendPrototypeDom($crawler->filter($filter)->getNode(0), $offset, $amount);

    $form = $formCrawler->form();
    $values = $form->getValues();

    for ($i = $offset; $i < $offset + $amount; $i++) {
      $values[$prefix.'['.$i.'][nome]'] = $i.'pippo'.md5($i.time());
      $values[$prefix.'['.$i.'][cognome]'] = $i.'pippo'.md5($i.time());
      $values[$prefix.'['.$i.'][codiceFiscale]'] = $i.'pippo'.md5($i.time());
      $values[$prefix.'['.$i.'][rapportoParentela]'] = $i.'pippo'.md5($i.time());
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
  protected function datiRichiedente(&$crawler, $nextButton, &$fillData, &$form, $asTecnicoAbilitato = false)
  {
    if ($asTecnicoAbilitato) {
      $this->assertEquals(1, $crawler->filter('input[name="pratica_richiedente[disclaimer_tecnico]"]')->count());
    }

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
   * @param Crawler $crawler
   * @param $nextButton
   * @param $fillData
   * @param $form
   */
  protected function datiDelega(&$crawler, $nextButton, &$fillData, &$form)
  {

    /*$fillData = array();
    $form = $crawler->selectButton($nextButton)->form(array(
        'pratica_delega[delega_type]' => Pratica::TIPO_DELEGA_DELEGATO,
    ));
    $crawler = $this->client->submit($form);
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");

    $msg = trim($crawler->filter('.alert-danger ul')->first()->text());
    $this->assertEquals($msg, "Se hai selezionato un tipo di delega devi specificare anche gli altri valori", "Delega selezionata e campi non riempiti");*/

    $fillData = array();
    // Todo: sistemare per cambiamenti delega voluti daRovereto
    /*$crawler->filter('form[name="pratica_delega"] input[type="text"]')
        ->each(function ($node, $i) use (&$fillData) {
            self::fillFormInputWithDummyText($node, $i, $fillData);
        });
    $fillData['pratica_delega[delega_type]'] = Pratica::TIPO_DELEGA_DELEGATO;*/
    $fillData['pratica_delega[has_delega]'] = 0;
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
  protected function datiCertificatoAnagrafico(&$crawler, $nextButton, &$fillData, &$form)
  {
    $fillData = array();
    $crawler->filter('form[name="pratica_certificato_anagrafico"] input[type="text"]')
      ->each(function ($node, $i) use (&$fillData) {
        self::fillFormInputWithDummyText($node, $i, $fillData);
      });
    $fillData['pratica_certificato_anagrafico[tipologia_certificato_anagrafico]'] = 'semplice';
    $form = $crawler->selectButton($nextButton)->form($fillData);
    $crawler = $this->client->submit($form);
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
  }

  /**
   * @param $numberOfExpectedAttachments
   * @param CPSUser $user
   *
   * @return Allegato[]
   * @throws \Doctrine\ORM\ORMException
   * @throws \Doctrine\ORM\OptimisticLockException
   */
  protected function setupNeededAllegatiForAllInvolvedUsers($numberOfExpectedAttachments, CPSUser $user)
  {
    $allegati = [];
    for ($i = 0; $i < $numberOfExpectedAttachments; $i++) {


      $allegato = new Allegato();
      $allegato->setOwner($user);
      $allegato->setDescription(self::CURRENT_USER_ALLEGATO_DESCRIPTION_PREFIX.$i);
      $allegato->setFilename('somefile.txt');
      $allegato->setOriginalFilename('somefile.txt');
      $allegato->setFile(new File(__DIR__.'/somefile.txt'));
      $this->em->persist($allegato);
      $allegati[] = $allegato;
    }

    $otherUser = $this->createCPSUser();
    $allegato = new Allegato();
    $allegato->setOwner($otherUser);
    $allegato->setDescription(self::OTHER_USER_ALLEGATO_DESCRIPTION);
    $allegato->setFilename('somefile.txt');
    $allegato->setOriginalFilename('somefile.txt');
    $allegato->setFile(new File(__DIR__.'/somefile.txt'));
    $this->em->persist($allegato);

    $this->em->flush();

    return $allegati;
  }

  /**
   * @param $numberOfExpectedAttachments
   * @param CPSUser $user
   *
   * @return AllegatoOperatore[]
   */
  protected function setupNeededAllegatiOperatoreForAllInvolvedUsers($numberOfExpectedAttachments, CPSUser $user)
  {
    $allegati = [];
    for ($i = 0; $i < $numberOfExpectedAttachments; $i++) {


      $allegato = new AllegatoOperatore();
      $allegato->setOwner($user);
      $allegato->setDescription(self::CURRENT_USER_ALLEGATO_DESCRIPTION_PREFIX.$i);
      $allegato->setFilename('somefile.txt');
      $allegato->setOriginalFilename('somefile.txt');
      $allegato->setFile(new File(__DIR__.'/somefile.txt'));
      $this->em->persist($allegato);
      $allegati[] = $allegato;
    }

    $otherUser = $this->createCPSUser();
    $allegato = new AllegatoOperatore();
    $allegato->setOwner($otherUser);
    $allegato->setDescription(self::OTHER_USER_ALLEGATO_DESCRIPTION);
    $allegato->setFilename('somefile.txt');
    $allegato->setOriginalFilename('somefile.txt');
    $allegato->setFile(new File(__DIR__.'/somefile.txt'));
    $this->em->persist($allegato);

    $this->em->flush();

    return $allegati;
  }

  /**
   * @param CPSUser $user
   *
   * @return RispostaOperatore[]
   */
  protected function setupRispostaOperatoreForAllInvolvedUsers(CPSUser $user)
  {
    $risposta = new RispostaOperatore();
    $risposta->setOwner($user);
    $risposta->setDescription('Risposta operatore p7m');
    $risposta->setFilename('risposta.pdf.p7m');
    $risposta->setOriginalFilename('risposta.pdf.p7m');
    $risposta->setFile(new File(__DIR__.'/risposta.pdf.p7m'));
    $this->em->persist($risposta);
    $this->em->flush();

    return $risposta;
  }

  protected static function fillFormInputWithDummyText(Crawler $node, $i, &$fillData, $dummyText = 'test')
  {
    $type = $node->attr('type');
    if ($type == 'number') {
      $dummyText = rand(0, time());
    }
    $name = $node->attr('name');
    $value = $node->attr('value');
    if (empty($value)) {
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

  /**
   * @param Crawler $crawler
   * @param $nextButton
   * @param $form
   */
  protected function addAllegatoOperatore(&$crawler, $nextButton, &$form)
  {
    // Selezione del comune
    $form = $crawler->selectButton($nextButton)->form();
    copy(__DIR__.'/test.pdf', __DIR__.'/run_test.pdf');
    $file = new UploadedFile(__DIR__.'/run_test.pdf', 'test.pdf', null, null, null, true);
    $formData = array('add' => $file);
    $form->submit($formData);
    $crawler = $this->client->submit($form);
    $this->assertEquals(200, $this->client->getResponse()->getStatusCode(), "Unexpected HTTP status code");
  }

  protected function createSubmittedPraticaForUser($user)
  {
    $ente = $this->createEnti()[0];
    $erogatore = $this->createErogatoreWithEnti([$ente]);
    $servizio = $this->createServizioWithErogatore(
      $erogatore,
      'Test protocollo',
      '\App\Entity\CertificatoNascita',
      'ocsdc.form.flow.certificatonascita'
    );
    $pratica = $this->createPratica($user, null, Pratica::STATUS_SUBMITTED, $erogatore, $servizio);
    $pratica->setEnte($ente);
    $moduloCompilato = self::$container->get('ocsdc.modulo_pdf_builder')->createForPratica($pratica, $user);
    $pratica->addModuloCompilato($moduloCompilato);

    return $pratica;
  }

  protected function getMockGuzzleClient(array $responses = [])
  {
    if (empty($responses)) {
      $responses = [$this->getPiTreSuccessResponse()];
    }
    $mock = new MockHandler($responses);

    $handler = HandlerStack::create($mock);

    return new GuzzleClient(['handler' => $handler]);
  }

  protected function getPiTreErrorResponse()
  {
    $body = [
      'status' => 'error',
      'message' => 'Test error',
    ];

    return new Response(200, [], json_encode($body));
  }

  protected function getInforSuccessResponse()
  {
    return <<<HEREDOC
<?xml version="1.0" ?>
 <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:ns1="http://webservices.jprotocollo.jente.infor.arezzo.it/">
  <soapenv:Body>
   <ns1:inserisciPartenzaResponse>
    <rispostaProtocolla>
     <ns1:esito>OK</ns1:esito>
     <ns1:segnatura>
      <ns1:registro>
       <ns1:codice>GE</ns1:codice>
       <ns1:descrizione>REGISTRO GENERALE</ns1:descrizione>
      </ns1:registro>
      <ns1:sezione>
       <ns1:codice>GE</ns1:codice>
       <ns1:descrizione>SEZIONE GENERALE</ns1:descrizione>
      </ns1:sezione>
      <ns1:anno>2018</ns1:anno>
      <ns1:numero>50</ns1:numero>
      <ns1:data>14/11/2018</ns1:data>
      <ns1:ora>18:37</ns1:ora>
      <ns1:amministrazione>
       <ns1:ente>
        <ns1:codice>c_h612</ns1:codice>
        <ns1:descrizione>Comune di Rovereto</ns1:descrizione>
       </ns1:ente>
       <ns1:aoo>
        <ns1:codice>c_h612</ns1:codice>
        <ns1:descrizione>Comune di Rovereto</ns1:descrizione>
       </ns1:aoo>
      </ns1:amministrazione>
     </ns1:segnatura>
    </rispostaProtocolla>
   </ns1:inserisciPartenzaResponse>
  </soapenv:Body>
 </soapenv:Envelope>
</xml>
HEREDOC;
  }

  protected function getPiTreSuccessResponse()
  {
    $body = [
      'status' => 'success',
      'message' => 'Elaborazione eseguita correttamente',
      'data' => [
        "id_doc" => md5(rand(0, 100).microtime()),
        "n_prot" => md5(rand(100, 200).microtime()),
      ],
    ];

    return new Response(200, [], json_encode($body));
  }

  protected function doTestISeeMyNameAsLoggedInUser(
    \App\Entity\User $user,
    \Symfony\Component\HttpFoundation\Response $response
  ) {
    $this->assertContains($user->getFullName(), $response->getContent());
  }

  protected function submitAsCPSUser(CPSUser $user, Form $form)
  {
    return $this->clientRequestAsCPSUser($user, $form->getMethod(), $form->getUri(), $form->getPhpValues());
  }

  protected function createDefaultTerm($mandatory = true)
  {
    $term = new TerminiUtilizzo();
    $term->setName('memento mori')
      ->setText('Ricordati che devi Rovereto')
      ->setMandatory($mandatory);
    $this->em->persist($term);
    $this->em->flush();
  }

  protected function createAllegatoForUser(User $user, $description = 'some description', $fileName = 'somefile.txt')
  {
    $allegato = new Allegato();
    $allegato->setOwner($user);
    $allegato->setDescription($description);
    $allegato->setFilename($fileName);
    $allegato->setOriginalFilename($fileName);
    $this->em->persist($allegato);
    $this->em->flush();

    return $allegato;
  }

  /**
   * @param array $relatedCFs
   *
   * @return SciaPraticaEdilizia
   */
  protected function setupPraticaScia($relatedCFs = [], $withProtocolli = false)
  {
    $ente = $this->createEnti()[0];
    $erogatore = $this->createErogatoreWithEnti([$ente]);
    $fqcn = SciaPraticaEdilizia::class;
    $flow = 'ocsdc.form.flow.scia_pratica_edilizia';
    $servizio = $this->createServizioWithErogatore($erogatore, 'Scia', $fqcn, $flow);

    $geometra = $this->createCPSUser(true, true);

    /** @var SciaPraticaEdilizia $pratica */
    $pratica = $this->createPratica($geometra, null, null, $erogatore, $servizio);
    $allegati = new ArrayCollection();
    for ($i = 0; $i < 3; $i++) {
      $allegati->add($this->createAllegatoForUser($geometra, 'some description', 'signed.pdf.p7m'));
    }

    $praticaScia = (new MappedPraticaEdilizia($pratica->getDematerializedForms()))
      ->setModuloDomanda(
        new GiscomFile([
          'name' => $allegati[0]->getName(),
          'id' => $allegati[0]->getId(),
          'type' => 'scia_ediliza_modulo_scia',
        ])
      )
      ->setElencoSoggettiAventiTitolo(
        new ElencoSoggettiAventiTitolo([
          [
            'name' => $allegati[1]->getName(),
            'id' => $allegati[1]->getId(),
          ],
        ])
      )
      ->setElencoAllegatoTecnici(
        'TEC_URB',
        new GiscomFileCollection([
          [
            'name' => $allegati[2]->getName(),
            'id' => $allegati[2]->getId(),
          ],
        ])
      );

    $dematerialized = $praticaScia->toHash();

    $pratica->setDematerializedForms($dematerialized);
    $pratica->setRelatedCFs($relatedCFs);
    if ($withProtocolli) {
      $pratica->setNumeriProtocollo(new ArrayCollection(array(['id' => 1, 'protocollo' => 1])));
      $pratica->setNumeroProtocollo(1);
      $pratica->setNumeroFascicolo(1);
    }
    $this->em->persist($pratica);
    $this->em->flush();

    return $pratica;
  }

}

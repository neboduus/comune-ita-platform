<?php

namespace App\Tests\Base;

use App\Entity\Allegato;
use App\Entity\AllegatoOperatore;
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
use App\Services\CPSUserProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use EightPoints\Bundle\GuzzleBundle\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\File;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * Class AbstractAppTestCase
 *
 * @package App\Tests\Base
 */
abstract class AbstractAppTestCase extends WebTestCase
{
  const OTHER_USER_ALLEGATO_DESCRIPTION = 'other';
  const CURRENT_USER_ALLEGATO_DESCRIPTION_PREFIX = 'description_';

  private $userProvider;

  /**
   * @inheritdoc
   */
  public function setUp(): void
  {
    parent::setUp();
    static::bootKernel();
    $this->userProvider = static::$container->get('ocsdc.cps.userprovider');
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

  protected function createCPSUser( $termAccepted = true, $profileData = true, $additionalRole = null )
  {

    $data = $profileData ? $this->getCPSUserData() : $this->getCPSUserBaseData();
    $user = $this->userProvider->provideUser($data);

    if ($termAccepted) {
      $term = new TerminiUtilizzo();
      $user->addTermsAcceptance($term);
    }

    if ($additionalRole) {
      $user->addRole($additionalRole);
    }

    return $user;
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

    $servizio = new Servizio();
    $servizio
      ->setName($name.'_'.md5(rand(0, 100).microtime()))
      ->setErogatori($erogatori)
      ->setDescription(
        'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer ultricies eros eu dignissim bibendum. Praesent tortor nibh, sodales vel ante quis, ultrices consequat ipsum. Praesent vestibulum vel eros nec consectetur. Phasellus et eros vestibulum, ultrices nisl nec, pharetra velit. Donec in ex fermentum, accumsan eros ac, convallis nulla. Donec ut suscipit purus, eget dignissim odio. Duis a congue felis.'
      )
      //->setArea($area)
      ->setPraticaFCQN($praticaFCQN)
      ->setPraticaFlowServiceName($praticaFlowServiceName)
      ->setPraticaFlowOperatoreServiceName($praticaFlowOperatoreServiceName);

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

    return $erogatore;
  }

  /**
   * @return Collection
   */
  protected function createEnti()
  {
    $ente1 = new Ente();
    // Modifico in comune di tre ville per avere uniformità tra enti creati ed identificatore del test
    $ente1->setName('Comune di Tre Ville');
    $ente1->setCodiceMeccanografico('L378');
    $ente1->setCodiceAmministrativo('L378');
    $ente1->setSiteUrl('http://example.com');

    $ente2 = new Ente();
    $ente2->setName('Ente di prova 2');
    $ente2->setCodiceMeccanografico('L781');
    $ente2->setCodiceAmministrativo('L781');
    $ente2->setSiteUrl('http://example.com');

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


    return null;
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
        $this->callback(
          function ($subject) use ($expectedArgs) {
            return in_array($subject, $expectedArgs);
          }
        )
      );

    /*static::$kernel->setKernelModifier(
      function (KernelInterface $kernel) use ($mockLogger) {
        $kernel->getContainer()->set('logger', $mockLogger);
      }
    );*/
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

    return $servizio;
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

    return $risposta;
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
    $moduloCompilato = static::$container->get('ocsdc.modulo_pdf_builder')->createForPratica($pratica, $user);
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



  protected function createDefaultTerm($mandatory = true)
  {
    $term = new TerminiUtilizzo();
    $term->setName('memento mori')
      ->setText('Ricordati che devi Rovereto')
      ->setMandatory($mandatory);
  }

  protected function createAllegatoForUser(User $user, $description = 'some description', $fileName = 'somefile.txt')
  {
    $allegato = new Allegato();
    $allegato->setOwner($user);
    $allegato->setDescription($description);
    $allegato->setFilename($fileName);
    $allegato->setOriginalFilename($fileName);

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
        new GiscomFile(
          [
            'name' => $allegati[0]->getName(),
            'id' => $allegati[0]->getId(),
            'type' => 'scia_ediliza_modulo_scia',
          ]
        )
      )
      ->setElencoSoggettiAventiTitolo(
        new ElencoSoggettiAventiTitolo(
          [
            [
              'name' => $allegati[1]->getName(),
              'id' => $allegati[1]->getId(),
            ],
          ]
        )
      )
      ->setElencoAllegatoTecnici(
        'TEC_URB',
        new GiscomFileCollection(
          [
            [
              'name' => $allegati[2]->getName(),
              'id' => $allegati[2]->getId(),
            ],
          ]
        )
      );

    $dematerialized = $praticaScia->toHash();

    $pratica->setDematerializedForms($dematerialized);
    $pratica->setRelatedCFs($relatedCFs);
    if ($withProtocolli) {
      $pratica->setNumeriProtocollo(new ArrayCollection(array(['id' => 1, 'protocollo' => 1])));
      $pratica->setNumeroProtocollo(1);
      $pratica->setNumeroFascicolo(1);
    }

    return $pratica;
  }
}

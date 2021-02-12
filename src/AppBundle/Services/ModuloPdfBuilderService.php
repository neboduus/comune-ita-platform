<?php

namespace AppBundle\Services;


use AppBundle\Entity\Allegato;
use AppBundle\Entity\GiscomPratica;
use AppBundle\Entity\Integrazione;
use AppBundle\Entity\IntegrazioneRepository;
use AppBundle\Entity\RichiestaIntegrazione;
use AppBundle\Entity\RichiestaIntegrazioneDTO;
use AppBundle\Entity\RichiestaIntegrazioneRequestInterface;
use AppBundle\Entity\ModuloCompilato;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\RispostaIntegrazione;
use AppBundle\Entity\RispostaOperatore;
use AppBundle\Entity\RispostaOperatoreDTO;
use AppBundle\Entity\Ritiro;
use AppBundle\Entity\ScheduledAction;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\SubscriptionPayment;
use AppBundle\Model\FeedbackMessage;
use AppBundle\ScheduledAction\Exception\AlreadyScheduledException;
use AppBundle\ScheduledAction\ScheduledActionHandlerInterface;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use ReflectionClass;
use ReflectionException;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;
use TheCodingMachine\Gotenberg\Client;

use TheCodingMachine\Gotenberg\ClientException;
use TheCodingMachine\Gotenberg\DocumentFactory;
use TheCodingMachine\Gotenberg\HTMLRequest;
use TheCodingMachine\Gotenberg\Request as GotembergRequest;
use TheCodingMachine\Gotenberg\RequestException;
use TheCodingMachine\Gotenberg\URLRequest;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

class ModuloPdfBuilderService implements ScheduledActionHandlerInterface
{

  const SCHEDULED_CREATE_FOR_PRATICA = 'createForPratica';
  const PRINTABLE_USERNAME = 'ez';

  /**
   * @var Filesystem
   */
  private $filesystem;

  /**
   * @var EntityManagerInterface
   */
  private $em;

  /**
   * @var TranslatorInterface
   */
  private $translator;

  /**
   * @var PropertyMappingFactory
   */
  private $propertyMappingFactory;

  /**
   * @var DirectoryNamerInterface
   */
  private $directoryNamer;

  /**
   * @var string
   */
  private $wkhtmltopdfService;

  /**
   * @var EngineInterface
   */
  private $templating;

  /**
   * @var string
   */
  private $dateTimeFormat;

  /**
   * @var UrlGeneratorInterface
   */
  private $router;

  /**
   * @var string
   */
  private $printablePassword;

  /**
   * @var PraticaStatusService
   */
  private $statusService;

  /**
   * @var ScheduleActionService
   */
  private $scheduleActionService;

  public function __construct(
    Filesystem $filesystem,
    EntityManagerInterface $em,
    TranslatorInterface $translator,
    PropertyMappingFactory $propertyMappingFactory,
    DirectoryNamerInterface $directoryNamer,
    string $wkhtmltopdfService,
    EngineInterface $templating,
    $dateTimeFormat,
    UrlGeneratorInterface $router,
    string $printablePassword,
    PraticaStatusService $statusService,
    ScheduleActionService $scheduleActionService
  )
  {
    $this->filesystem = $filesystem;
    $this->em = $em;
    $this->translator = $translator;
    $this->propertyMappingFactory = $propertyMappingFactory;
    $this->directoryNamer = $directoryNamer;
    $this->wkhtmltopdfService = $wkhtmltopdfService;
    $this->templating = $templating;
    $this->dateTimeFormat = $dateTimeFormat;
    $this->router = $router;
    $this->printablePassword = $printablePassword;
    $this->statusService = $statusService;
    $this->scheduleActionService = $scheduleActionService;
  }

  /**
   * @param Pratica $pratica
   *
   * @return RispostaOperatore
   * @throws Exception
   */
  public function createUnsignedResponseForPratica(Pratica $pratica)
  {
    $unsignedResponse = new RispostaOperatore();
    $this->createAllegatoInstance($pratica, $unsignedResponse);
    $servizioName = $pratica->getServizio()->getName();
    $now = new DateTime();
    $now->setTimestamp(time());
    $unsignedResponse->setOriginalFilename("Servizio {$servizioName} " . $now->format('Ymdhi'));
    $unsignedResponse->setDescription(
      $this->translator->trans(
        'pratica.modulo.descrizioneRisposta',
        [
          'nomeservizio' => $pratica->getServizio()->getName(),
          'datacompilazione' => $now->format($this->dateTimeFormat)
        ])
    );
    $this->em->persist($unsignedResponse);
    return $unsignedResponse;
  }


  /**
   * @param Pratica $pratica
   *
   * @return RispostaOperatore
   * @throws Exception
   */
  public function createSignedResponseForPratica(Pratica $pratica)
  {
    $signedResponse = new RispostaOperatore();
    $this->createAllegatoInstance($pratica, $signedResponse);
    $servizioName = $pratica->getServizio()->getName();
    $now = new DateTime();
    $now->setTimestamp(time());
    $signedResponse->setOriginalFilename("Servizio {$servizioName} " . $now->format('Ymdhi'));
    $signedResponse->setDescription(
      $this->translator->trans(
        'pratica.modulo.descrizioneRisposta',
        [
          'nomeservizio' => $pratica->getServizio()->getName(),
          'datacompilazione' => $now->format($this->dateTimeFormat)
        ])
    );
    $this->em->persist($signedResponse);
    return $signedResponse;
  }

  /**
   * @param Pratica $pratica
   *
   * @return Ritiro
   * @throws Exception
   */
  public function createWithdrawForPratica(Pratica $pratica)
  {
    $withdrawAttachment = new Ritiro();
    $this->createAllegatoInstance($pratica, $withdrawAttachment);
    $servizioName = $pratica->getServizio()->getName();
    $now = new DateTime();
    $now->setTimestamp(time());
    $withdrawAttachment->setOriginalFilename("Ritiro servizio {$servizioName} " . $now->format('Ymdhi'));
    $withdrawAttachment->setDescription("Ritiro servizio {$servizioName} " . $now->format('Ymdhi'));
    $this->em->persist($withdrawAttachment);
    return $withdrawAttachment;
  }


  /**
   * @param Pratica $pratica
   *
   * @return ModuloCompilato
   * @throws Exception
   */
  public function createForPratica(Pratica $pratica)
  {
    $moduloCompilato = new ModuloCompilato();
    $this->createAllegatoInstance($pratica, $moduloCompilato);
    $servizioName = $pratica->getServizio()->getName();
    $now = new DateTime();
    $now->setTimestamp($pratica->getSubmissionTime());
    $moduloCompilato->setOriginalFilename("Modulo {$servizioName} " . $now->format('Ymdhi'));
    $moduloCompilato->setDescription(
      $this->translator->trans(
        'pratica.modulo.descrizione',
        [
          'nomeservizio' => $pratica->getServizio()->getName(),
          'datacompilazione' => $now->format($this->dateTimeFormat)
        ])
    );
    $this->em->persist($moduloCompilato);

    return $moduloCompilato;
  }

  /**
   * @param Pratica $pratica
   *
   * @return ModuloCompilato
   * @throws Exception
   */
  public function showForPratica(Pratica $pratica)
  {
    $allegato = new ModuloCompilato();
    $content = $this->renderForPratica($pratica);

    $allegato->setOwner($pratica->getUser());
    $destinationDirectory = $this->getDestinationDirectoryFromContext($allegato);
    $fileName = $pratica->getId() . '-prot.pdf';
    $filePath = $destinationDirectory . DIRECTORY_SEPARATOR . $fileName;

    $this->filesystem->dumpFile($filePath, $content);
    $allegato->setFile(new File($filePath));
    $allegato->setFilename($fileName);


    $servizioName = $pratica->getServizio()->getName();
    $now = new DateTime();
    $now->setTimestamp($pratica->getSubmissionTime());
    $allegato->setOriginalFilename("Modulo {$servizioName} " . $now->format('Ymdhi'));

    return $allegato;
  }


  /**
   * @param Pratica $pratica
   * @param RichiestaIntegrazioneDTO $integrationRequest
   *
   * @return RichiestaIntegrazione
   * @throws Exception
   */
  public function creaModuloProtocollabilePerRichiestaIntegrazione(
    Pratica $pratica,
    RichiestaIntegrazioneDTO $integrationRequest
  )
  {
    $integration = new RichiestaIntegrazione();
    $payload = $integrationRequest->getPayload();

    if (isset($payload['FileRichiesta']) && !empty($payload['FileRichiesta'])) {
      $content = base64_decode($payload['FileRichiesta']);
      unset($payload['FileRichiesta']);
      $fileName = uniqid() . '.pdf.p7m';
    } else {
      $content = $this->renderForPraticaIntegrationRequest($pratica, $integrationRequest);
      $fileName = uniqid() . '.pdf';
    }

    $integration->setPayload($payload);
    $integration->setOwner($pratica->getUser());
    $integration->setOriginalFilename($fileName);
    $integration->setDescription($integrationRequest->getMessage());
    $integration->setPratica($pratica);

    $destinationDirectory = $this->getDestinationDirectoryFromContext($integration);
    $filePath = $destinationDirectory . DIRECTORY_SEPARATOR . $fileName;

    $this->filesystem->dumpFile($filePath, $content);
    $integration->setFile(new File($filePath));
    $integration->setFilename($fileName);

    $this->em->persist($integration);

    return $integration;
  }

  /**
   * @param Pratica $pratica
   *
   * @return RispostaIntegrazione
   * @throws Exception
   */
  public function creaModuloProtocollabilePerRispostaIntegrazione(Pratica $pratica)
  {

    $integrationRequest = $pratica->getRichiestaDiIntegrazioneAttiva();

    $attachment = new RispostaIntegrazione();
    $content = $this->renderForPraticaIntegrationAnswer($pratica);
    $fileName = uniqid() . '.pdf';
    $attachment->setIdRichiestaIntegrazione($integrationRequest->getId());

    $attachment->setOwner($pratica->getUser());
    $attachment->setOriginalFilename($fileName);
    $attachment->setDescription('Risposta a richiesta integrazione: ' . $integrationRequest->getId());

    $destinationDirectory = $this->getDestinationDirectoryFromContext($attachment);
    $filePath = $destinationDirectory . DIRECTORY_SEPARATOR . $fileName;

    $this->filesystem->dumpFile($filePath, $content);
    $attachment->setFile(new File($filePath));
    $attachment->setFilename($fileName);

    $this->em->persist($attachment);

    return $attachment;
  }

  /**
   * @param Pratica $pratica
   * @param RispostaOperatoreDTO $rispostaOperatore
   * @return RispostaOperatore|null
   * @throws Exception
   */
  public function creaRispostaOperatore(Pratica $pratica, RispostaOperatoreDTO $rispostaOperatore)
  {
    $response = new RispostaOperatore();
    $payload = $rispostaOperatore->getPayload();

    if (isset($payload['FileRichiesta']) && !empty($payload['FileRichiesta'])) {
      $content = base64_decode($payload['FileRichiesta']);
      unset($payload['FileRichiesta']);
      $fileName = uniqid() . '.p7m';

      $response->setOwner($pratica->getUser());
      $response->setOriginalFilename((new DateTime())->format('Ymdhi'));
      $response->setDescription($rispostaOperatore->getMessage() ?? '');

      $destinationDirectory = $this->getDestinationDirectoryFromContext($response);
      $filePath = $destinationDirectory . DIRECTORY_SEPARATOR . $fileName;

      $this->filesystem->dumpFile($filePath, $content);
      $response->setFile(new File($filePath));
      $response->setFilename($fileName);

      $this->em->persist($response);

    } else {
      $response = $this->createUnsignedResponseForPratica($pratica);
    }
    return $response;
  }

  /**
   * @param Pratica $pratica
   * @param RichiestaIntegrazioneDTO $integrationRequest
   * @return string
   */
  private function renderForPraticaIntegrationRequest(Pratica $pratica, RichiestaIntegrazioneDTO $integrationRequest)
  {
    $html = $this->templating->render('AppBundle:Pratiche:pdf/parts/integration.html.twig', [
      'pratica' => $pratica,
      'richiesta_integrazione' => $integrationRequest,
      'user' => $pratica->getUser(),
    ]);

    return $this->generatePdf($html);
  }

  /**
   * @param Pratica $pratica
   * @return string
   */
  private function renderForPraticaIntegrationAnswer(Pratica $pratica)
  {

    $integrationRequest = $pratica->getRichiestaDiIntegrazioneAttiva();
    /** @var IntegrazioneRepository $integrationRepo */
    $integrationRepo = $this->em->getRepository('AppBundle:Integrazione');

    /** @var Integrazione[] $integrations */
    $integrations = $integrationRepo->findByIntegrationRequest($integrationRequest->getId());

    $html = $this->templating->render('AppBundle:Pratiche:pdf/parts/answer_integration.html.twig', [
      'pratica' => $pratica,
      'richiesta_integrazione' => $integrationRequest,
      'integrazioni' => $integrations,
      'user' => $pratica->getUser(),
    ]);

    return $this->generatePdf($html);
  }

  /**
   * @param Pratica $pratica
   *
   * @return string
   * @throws ClientException
   * @throws RequestException
   * @throws ReflectionException
   */
  private function renderForPratica(Pratica $pratica)
  {

    $className = (new ReflectionClass($pratica))->getShortName();
    if ($className == 'FormIO') {
      return $this->generatePdfUsingGotemberg( $pratica );
    } else {
      return $this->renderForClass($pratica, $className);
    }
  }

  /**
   * @param Pratica $pratica
   *
   * @return string
   */
  public function renderForResponse(Pratica $pratica)
  {
    // Risposta Operatore di default
    $html = $this->templating->render('AppBundle:Pratiche:pdf/RispostaOperatore.html.twig', [
      'pratica' => $pratica,
      'user' => $pratica->getUser(),
    ]);

    $feedbackMessages = $pratica->getServizio()->getFeedbackMessages();
    $status = $pratica->getEsito() ? Pratica::STATUS_COMPLETE : Pratica::STATUS_CANCELLED;
    if (isset($feedbackMessages[$status])) {
      /** @var FeedbackMessage $feedbackMessage */
      $feedbackMessage = $feedbackMessages[$status];
      $submissionTime = $pratica->getSubmissionTime() ? (new \DateTime())->setTimestamp($pratica->getSubmissionTime()) : null;
      $protocolTime = $pratica->getProtocolTime() ? (new \DateTime())->setTimestamp($pratica->getProtocolTime()) : null;

      $placeholders = [
        '%pratica_id%' => $pratica->getId(),
        '%servizio%' => $pratica->getServizio()->getName(),
        '%protocollo%' => $pratica->getNumeroProtocollo() ? $pratica->getNumeroProtocollo() : $this->translator->trans('email.pratica.no_info'),
        '%messaggio_personale%' => !empty(trim($pratica->getMotivazioneEsito())) ? $pratica->getMotivazioneEsito() : $this->translator->trans('messages.pratica.no_reason'),
        '%user_name%' => $pratica->getUser()->getFullName(),
        '%indirizzo%' => $this->router->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL),
        '%data_acquisizione%' => $submissionTime ? $submissionTime->format('d/m/Y') : $this->translator->trans('email.pratica.no_info'),
        '%ora_acquisizione%' => $submissionTime ? $submissionTime->format('H:i:s') : $this->translator->trans('email.pratica.no_info'),
        '%data_protocollo%' => $protocolTime ? $protocolTime->format('d/m/Y') : $this->translator->trans('email.pratica.no_info'),
        '%ora_protocollo%' => $protocolTime ? $protocolTime->format('H:i:s') : $this->translator->trans('email.pratica.no_info')
      ];

      $html = $this->templating->render(
        'AppBundle:Pratiche:pdf/RispostaOperatoreCustom.html.twig',
        array(
          'pratica' => $pratica,
          'placeholder' => $placeholders,
          'text' => strtr($feedbackMessage['message'], $placeholders),
        )
      );
    }

    return $this->generatePdf($html);

  }

  /**
   * @param Pratica $pratica
   *
   * @return string
   */
  public function renderForWithdraw(Pratica $pratica)
  {
    $className = (new ReflectionClass(Ritiro::class))->getShortName();
    return $this->renderForClass($pratica, $className);
  }

  /**
   * @param Allegato $moduloCompilato
   *
   * @return string
   */
  private function getDestinationDirectoryFromContext(Allegato $moduloCompilato)
  {
    $mapping = $this->propertyMappingFactory->fromObject($moduloCompilato)[0];
    $path = $this->directoryNamer->directoryName($moduloCompilato, $mapping);

    return $mapping->getUploadDestination() . '/' . $path;
  }

  /**
   * @param Pratica $pratica
   * @param Allegato $allegato
   * @throws ClientException
   * @throws RequestException
   * @throws ReflectionException
   */
  private function createAllegatoInstance(Pratica $pratica, Allegato $allegato)
  {
    $allegato->setOwner($pratica->getUser());
    $destinationDirectory = $this->getDestinationDirectoryFromContext($allegato);
    $fileName = uniqid() . '.pdf';
    $filePath = $destinationDirectory . DIRECTORY_SEPARATOR . $fileName;
    $content = null;

    if ($allegato instanceof RispostaOperatore) {
      $content = $this->renderForResponse($pratica);
      $this->filesystem->dumpFile($filePath, $content);
    } else if ($allegato instanceof Ritiro) {
      $content = $this->renderForWithdraw($pratica);
      $this->filesystem->dumpFile($filePath, $content);
    } else {
      $content = $this->renderForPratica($pratica);
      $this->filesystem->dumpFile($filePath, $content);
    }

    $allegato->setFile(new File($filePath));
    $allegato->setFilename($fileName);
  }

  /**
   * @param Pratica $pratica
   * @param $className
   * @return string
   */
  private function renderForClass(Pratica $pratica, $className)
  {
    $html = $this->templating->render('AppBundle:Pratiche:pdf/' . $className . '.html.twig', [
      'pratica' => $pratica,
      'user' => $pratica->getUser(),
    ]);

    return $this->generatePdf($html);
  }

  /**
   * @param $html
   * @return string
   */
  private function generatePdf($html)
  {
    $client = new Client($this->wkhtmltopdfService, new \Http\Adapter\Guzzle6\Client());

    try {
      $index = DocumentFactory::makeFromString('index.html', $html);

      $request = new HTMLRequest($index);
      $request->setPaperSize(GotembergRequest::A4);
      $request->setMargins(GotembergRequest::NO_MARGINS);
      $response =  $client->post($request);
      $fileStream = $response->getBody();
      return $fileStream->getContents();

    } catch (RequestException $e) {
      # this exception is thrown if given paper size or margins are not correct.
    } catch (ClientException $e) {
      # this exception is thrown by the client if the API has returned a code != 200.
    } catch (Exception $e) {

    }
    return '';
  }

  /**
   * @param Pratica $pratica
   * @return string
   * @throws ClientException
   * @throws RequestException
   */
  public function generatePdfUsingGotemberg( Pratica $pratica )
  {

    $url = $this->router->generate('print_pratiche', ['pratica' => $pratica], UrlGeneratorInterface::ABSOLUTE_URL);
    $client = new Client($this->wkhtmltopdfService, new \Http\Adapter\Guzzle6\Client());
    $request = new URLRequest($url);
    $request->setPaperSize(GotembergRequest::A4);
    $request->setMargins(GotembergRequest::NO_MARGINS);
    $request->setWaitDelay(5);
    $request->addRemoteURLHTTPHeader('Authorization', 'Basic '.base64_encode(implode(':', ['ez', $this->printablePassword])));
    $response =  $client->post($request);
    $fileStream = $response->getBody();
    return $fileStream->getContents();

  }

  /**
   * @param Servizio $service
   * @return string
   * @throws ClientException
   * @throws RequestException
   */
  public function generateServicePdfUsingGotemberg( Servizio $service )
  {
    $url = $this->router->generate('print_service', ['service' => $service->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
    $client = new Client($this->wkhtmltopdfService, new \Http\Adapter\Guzzle6\Client());
    $request = new URLRequest($url);
    $request->setPaperSize(GotembergRequest::A4);
    $request->setMargins(GotembergRequest::NO_MARGINS);
    $request->setWaitDelay(5);
    $response =  $client->post($request);
    $fileStream = $response->getBody();
    return $fileStream->getContents();

  }

  /**
   * @param Pratica|GiscomPratica $pratica
   * @throws AlreadyScheduledException
   */
  public function createForPraticaAsync(Pratica $pratica)
  {
    $params = serialize(['pratica' => $pratica->getId(),]);

    $this->scheduleActionService->appendAction(
      'ocsdc.modulo_pdf_builder',
      self::SCHEDULED_CREATE_FOR_PRATICA,
      $params
    );
  }

  /**
   * @param SubscriptionPayment $payment
   *
   * @return string
   */
  public function renderForSubscriptionPayment(SubscriptionPayment $payment)
  {
    // Certificato di default
    $html = $this->templating->render('AppBundle:Subscriptions/pdf:Payment.html.twig', [
      "payment"=>$payment,
    ]);

    return $this->generatePdf($html);
  }

  /**
   * @param ScheduledAction $action
   * @throws Exception
   */
  public function executeScheduledAction(ScheduledAction $action)
  {
    $params = unserialize($action->getParams());
    if ($action->getType() == self::SCHEDULED_CREATE_FOR_PRATICA) {
      /** @var Pratica $pratica */
      $pratica = $this->em->getRepository('AppBundle:Pratica')->find($params['pratica']);
      if (!$pratica instanceof Pratica) {
        throw new Exception('Not found application with id: ' . $params['pratica']);
      }
      $pdf = $this->createForPratica($pratica);
      $pratica->addModuloCompilato($pdf);
      $this->statusService->setNewStatus($pratica, Pratica::STATUS_SUBMITTED);
    }
  }
}

<?php

namespace App\Services;


use App\Entity\Allegato;
use App\Entity\GiscomPratica;
use App\Entity\Integrazione;
use App\Entity\IntegrazioneRepository;
use App\Entity\Message;
use App\Entity\ModuloCompilato;
use App\Entity\Pratica;
use App\Entity\RichiestaIntegrazione;
use App\Entity\RichiestaIntegrazioneDTO;
use App\Entity\RichiestaIntegrazioneRequestInterface;
use App\Entity\RispostaIntegrazione;
use App\Entity\RispostaOperatore;
use App\Entity\RispostaOperatoreDTO;
use App\Entity\Ritiro;
use App\Entity\ScheduledAction;
use App\Entity\Servizio;
use App\Entity\SubscriptionPayment;
use App\Model\FeedbackMessage;
use App\ScheduledAction\Exception\AlreadyScheduledException;
use App\ScheduledAction\ScheduledActionHandlerInterface;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TheCodingMachine\Gotenberg\Client;
use TheCodingMachine\Gotenberg\ClientException;
use TheCodingMachine\Gotenberg\DocumentFactory;
use TheCodingMachine\Gotenberg\HTMLRequest;
use TheCodingMachine\Gotenberg\Request as GotembergRequest;
use TheCodingMachine\Gotenberg\RequestException;
use TheCodingMachine\Gotenberg\URLRequest;
use Twig\Environment;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

class ModuloPdfBuilderService implements ScheduledActionHandlerInterface
{

  const MIME_TYPE = 'application/pdf';

  const SCHEDULED_CREATE_FOR_PRATICA = 'createForPratica';
  const PRINTABLE_USERNAME = 'ez';

  /**
   * @var FileSystemService
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
   * @var Environment
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

  /**
   * @var LoggerInterface
   */
  private $logger;
  /**
   * @var PraticaPlaceholderService
   */
  private $praticaPlaceholderService;

  /**
   * @param FileSystemService $filesystem
   * @param EntityManagerInterface $em
   * @param TranslatorInterface $translator
   * @param PropertyMappingFactory $propertyMappingFactory
   * @param DirectoryNamerInterface $directoryNamer
   * @param string $wkhtmltopdfService
   * @param Environment $templating
   * @param $dateTimeFormat
   * @param UrlGeneratorInterface $router
   * @param string $printablePassword
   * @param PraticaStatusService $statusService
   * @param ScheduleActionService $scheduleActionService
   * @param LoggerInterface $logger
   * @param PraticaPlaceholderService $praticaPlaceholderService
   */
  public function __construct(
    FileSystemService $filesystem,
    EntityManagerInterface $em,
    TranslatorInterface $translator,
    PropertyMappingFactory $propertyMappingFactory,
    DirectoryNamerInterface $directoryNamer,
    string $wkhtmltopdfService,
    Environment $templating,
    $dateTimeFormat,
    UrlGeneratorInterface $router,
    string $printablePassword,
    PraticaStatusService $statusService,
    ScheduleActionService $scheduleActionService,
    LoggerInterface $logger,
    PraticaPlaceholderService $praticaPlaceholderService
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
    $this->logger = $logger;
    $this->praticaPlaceholderService = $praticaPlaceholderService;
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

    $fileName = $this->generateFileName("Risposta {$pratica->getServizio()->getName()}");
    $unsignedResponse->setOriginalFilename($fileName);
    $unsignedResponse->setDescription($fileName);

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

    $fileName = $this->generateFileName("Risposta {$pratica->getServizio()->getName()}");
    $signedResponse->setOriginalFilename($fileName);
    $signedResponse->setDescription($fileName);

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

    $fileName = $this->generateFileName("Ritiro pratica {$pratica->getServizio()->getName()}");
    $withdrawAttachment->setOriginalFilename($fileName);
    $withdrawAttachment->setDescription($fileName);

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

    $fileName = $this->generateFileName("Modulo {$pratica->getServizio()->getName()}");
    $moduloCompilato->setOriginalFilename($fileName);
    $moduloCompilato->setDescription($fileName);

    $this->em->persist($moduloCompilato);
    return $moduloCompilato;
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
      $integration->setMimeType('application/pkcs7-mime');
    } else {
      $content = $this->renderForPraticaIntegrationRequest($pratica, $integrationRequest);
      $fileName = uniqid() . '.pdf';
      $integration->setMimeType('application/pdf');
    }

    $integration->setPayload($payload);
    $integration->setOwner($pratica->getUser());
    $integration->setOriginalFilename($fileName);
    $integration->setDescription($integrationRequest->getMessage());
    $integration->setPratica($pratica);

    $destinationDirectory = $this->getDestinationDirectoryFromContext($integration);
    $filePath = $destinationDirectory . DIRECTORY_SEPARATOR . $fileName;

    $this->filesystem->getFilesystem()->write($filePath, $content);
    $integration->setFile(new File($filePath, false));
    $integration->setFilename($fileName);

    $this->em->persist($integration);

    return $integration;
  }

  /**
   * @param Pratica $pratica
   * @param array|null $messages
   * @param bool $cancel
   * @return RispostaIntegrazione
   * @throws \League\Flysystem\FileExistsException
   */
  public function creaModuloProtocollabilePerRispostaIntegrazione(Pratica $pratica, array $messages = null, $cancel = false)
  {

    $integrationRequest = $pratica->getRichiestaDiIntegrazioneAttiva();
    $payload[RichiestaIntegrazione::TYPE_DEFAULT] = $integrationRequest->getId();

    //Se messages è  null recupero i messaggi in automatico, per retrocompatibilità su prima versione
    if ($messages === null) {
      $repo = $this->em->getRepository('App\Entity\Pratica');
      $filters['from_date'] = $integrationRequest->getCreatedAt();
      $filters['visibility'] = Message::VISIBILITY_APPLICANT;
      $messages = $repo->getMessages($filters, $pratica);
    }

    if (!empty($messages)) {
      /** @var Message $m */
      foreach ($messages as $m) {
        $payload[RispostaIntegrazione::PAYLOAD_MESSAGES][]= $m->getId();
        /** @var Allegato $a */
        foreach ($m->getAttachments() as $a ) {
          $payload[RispostaIntegrazione::PAYLOAD_ATTACHMENTS][]= $a->getId();
        }
      }
    }

    if ($cancel) {
      $content = $this->renderForPraticaIntegrationAnswer($pratica, $integrationRequest, $messages, $cancel);
      $description = 'Cancellazione richiesta integrazione: ' . $integrationRequest->getId();
    } else {
      $content = $this->renderForPraticaIntegrationAnswer($pratica, $integrationRequest, $messages, $cancel);
      $description = 'Risposta a richiesta integrazione: ' . $integrationRequest->getId();
    }

    $fileName = uniqid() . '.pdf';
    $attachment = new RispostaIntegrazione();
    $attachment->setPayload($payload);
    $attachment->setOwner($pratica->getUser());
    $attachment->setOriginalFilename($fileName);
    $attachment->setDescription($description);

    $destinationDirectory = $this->getDestinationDirectoryFromContext($attachment);
    $filePath = $destinationDirectory . DIRECTORY_SEPARATOR . $fileName;

    $this->filesystem->getFilesystem()->write($filePath, $content);
    $attachment->setFile(new File($filePath, false));
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
      $uniqid = uniqid();
      $fileName = $uniqid . '.p7m';

      $response->setOwner($pratica->getUser());
      $response->setOriginalFilename($uniqid . '.pdf.p7m');
      $response->setDescription($rispostaOperatore->getMessage() ?? '');

      $destinationDirectory = $this->getDestinationDirectoryFromContext($response);
      $filePath = $destinationDirectory . DIRECTORY_SEPARATOR . $fileName;

      $this->filesystem->getFilesystem()->write($filePath, $content);
      $response->setFile(new File($filePath, false));
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
    $html = $this->templating->render('Pratiche/pdf/parts/integration.html.twig', [
      'pratica' => $pratica,
      'richiesta_integrazione' => $integrationRequest,
      'user' => $pratica->getUser(),
    ]);

    return $this->generatePdf($html);
  }

  /**
   * @param Pratica $pratica
   * @param RichiestaIntegrazione $integrationRequest
   * @param array|null $messages
   * @return string
   */
  private function renderForPraticaIntegrationAnswer(Pratica $pratica, RichiestaIntegrazione $integrationRequest, ?array $messages, $cancel)
  {

    /** @var IntegrazioneRepository $integrationRepo */
    $integrationRepo = $this->em->getRepository('App\Entity\Integrazione');

    /** @var Integrazione[] $integrations */
    $integrations = $integrationRepo->findByIntegrationRequest($integrationRequest->getId());

    if ($cancel) {
      $html = $this->templating->render('Pratiche/pdf/parts/cancel_integration.html.twig', [
        'pratica' => $pratica,
        'richiesta_integrazione' => $integrationRequest,
        'user' => $pratica->getUser(),
      ]);
    } else {
      $html = $this->templating->render('Pratiche/pdf/parts/answer_integration.html.twig', [
        'pratica' => $pratica,
        'richiesta_integrazione' => $integrationRequest,
        'integrazioni' => $integrations,
        'messages' => $messages ?? [],
        'user' => $pratica->getUser(),
      ]);
    }

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
  public function renderForPratica(Pratica $pratica, $showProtocolNumber = false)
  {

    $className = (new ReflectionClass($pratica))->getShortName();
    if ($className == 'FormIO') {
      return $this->generatePdfUsingGotemberg( $pratica, $showProtocolNumber );
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
    $html = $this->templating->render('Pratiche/pdf/RispostaOperatore.html.twig', [
      'pratica' => $pratica,
      'user' => $pratica->getUser(),
    ]);

    $feedbackMessages = $pratica->getServizio()->getFeedbackMessages();
    $status = $pratica->getEsito() ? Pratica::STATUS_COMPLETE : Pratica::STATUS_CANCELLED;
    if (isset($feedbackMessages[$status])) {
      /** @var FeedbackMessage $feedbackMessage */
      $feedbackMessage = $feedbackMessages[$status];

      $placeholders = $this->praticaPlaceholderService->getPlaceholders($pratica);

      $html = $this->templating->render(
        'Pratiche/pdf/RispostaOperatoreCustom.html.twig',
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

    return  $path;
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
      $this->filesystem->getFilesystem()->write($filePath, $content);
    } else if ($allegato instanceof Ritiro) {
      $content = $this->renderForWithdraw($pratica);
      $this->filesystem->getFilesystem()->write($filePath, $content);
    } else {
      $content = $this->renderForPratica($pratica);
      $this->filesystem->getFilesystem()->write($filePath, $content);
    }

    $allegato->setFile(new File($filePath, false));
    $allegato->setFilename($fileName);
    $allegato->setMimeType(self::MIME_TYPE);
  }

  /**
   * @param Pratica $pratica
   * @param $className
   * @return string
   */
  private function renderForClass(Pratica $pratica, $className)
  {
    $html = $this->templating->render('Pratiche/pdf/' . $className . '.html.twig', [
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
      //$request->setMargins(GotembergRequest::NO_MARGINS);
      $request->setMargins([1,0,0,0]);
      $response =  $client->post($request);
      $fileStream = $response->getBody();
      return $fileStream->getContents();

    } catch (RequestException $e) {
      # this exception is thrown if given paper size or margins are not correct.
      $this->logger->error($e->getMessage());
      return $e->getMessage();
    } catch (ClientException $e) {
      # this exception is thrown by the client if the API has returned a code != 200.
      $this->logger->error($e->getMessage());
      return $e->getMessage();
    } catch (Exception $e) {
      $this->logger->error($e->getMessage());
      return $e->getMessage();
    }
  }

  /**
   * @param $fileName
   * @return string
   */
  private function generateFileName($fileName)
  {
    $now = new DateTime();
    $now->setTimestamp(time());

    $fileName = ("{$fileName} " . $now->format('Ymdhi'));
    if (strlen($fileName) > 250) {
      $fileName = substr($fileName, 0, 250);
    }
    return $fileName . '.pdf';
  }

  /**
   * @param Pratica $pratica
   * @return string
   * @throws ClientException
   * @throws RequestException
   */
  public function generatePdfUsingGotemberg( Pratica $pratica, $showProtocolNumber = false )
  {
    $locale = $pratica->getLocale();
    $url = $this->router->generate('print_pratiche', ['_locale' => $locale, 'pratica' => $pratica, 'protocol' => $showProtocolNumber], UrlGeneratorInterface::ABSOLUTE_URL);
    $client = new Client($this->wkhtmltopdfService, new \Http\Adapter\Guzzle6\Client());
    $request = new URLRequest($url);
    $request->setPaperSize(GotembergRequest::A4);
    $request->setMargins([1,0,0,0]);
    //$request->setWaitTimeout(30);
    $request->setWaitDelay(20);
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
    $request->setMargins([1,0,0,0]);
    $request->setWaitDelay(20);
    $response =  $client->post($request);
    $fileStream = $response->getBody();
    return $fileStream->getContents();

  }

  /**
   * @param Pratica|GiscomPratica $pratica
   * @param bool $nextStatus
   * @throws AlreadyScheduledException
   */
  public function createForPraticaAsync(Pratica $pratica, $nextStatus = false)
  {
    $params = [
      'pratica' => $pratica->getId()
    ];

    if ($nextStatus) {
      $params['next_status'] = $nextStatus;
    }

    $this->scheduleActionService->appendAction(
      'ocsdc.modulo_pdf_builder',
      self::SCHEDULED_CREATE_FOR_PRATICA,
      serialize($params)
    );
  }

  /**
   * @param SubscriptionPayment $payment
   *
   * @return string
   */
  public function renderForSubscriptionPayment(SubscriptionPayment $payment)
  {
    $pratica = $this->em->getRepository(Pratica::class)->find($payment->getExternalKey());
    // Certificato di default
    $html = $this->templating->render('Subscriptions/pdf/Payment.html.twig', [
      "payment"=>$payment,
      "pratica"=>$pratica
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
      $pratica = $this->em->getRepository('App\Entity\Pratica')->find($params['pratica']);
      if (!$pratica instanceof Pratica) {
        throw new Exception('Not found application with id: ' . $params['pratica']);
      }

      // Todo: trovare una logica migliore e disaccoppiare la transizione del cambio stato con gli event listener
      // Serve ad evitare che un errore successivo (integrazioni backoffice, messaggi kafka, webhook) crei un pdf all'infinito
      if ($pratica->getModuliCompilati()->isEmpty()) {
        $pdf = $this->createForPratica($pratica);
        $pratica->addModuloCompilato($pdf);
      }

      if (isset($params['next_status']) && !empty($params['next_status'])) {
        $this->statusService->setNewStatus($pratica, $params['next_status'], null, true);
      } else {
        $this->statusService->setNewStatus($pratica, Pratica::STATUS_SUBMITTED);
      }
    }
  }
}

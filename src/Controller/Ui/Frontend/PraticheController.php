<?php

namespace App\Controller\Ui\Frontend;


use App\Entity\Allegato;
use App\Entity\CPSUser;
use App\Entity\Ente;
use App\Entity\GiscomPratica;
use App\Entity\Message;
use App\Entity\Nota;
use App\Entity\Pratica;
use App\Entity\PraticaRepository;
use App\Entity\Servizio;
use App\Form\Base\PraticaFlow;
use App\Form\PraticaFlowRegistry;
use App\FormIO\ExpressionValidator;
use App\Handlers\Servizio\ForbiddenAccessException;
use App\Handlers\Servizio\ServizioHandlerRegistry;
use App\Logging\LogConstants;
use App\Model\Transition;
use App\Security\Voters\ApplicationVoter;
use App\Services\BreadcrumbsService;
use App\Services\FormServerApiAdapterService;
use App\Services\InstanceService;
use App\Services\Manager\MessageManager;
use App\Services\Manager\PraticaManager;
use App\Services\Manager\ServiceManager;
use App\Services\ModuloPdfBuilderService;
use App\Services\PraticaStatusService;
use App\Utils\iCalUtils;
use App\Utils\StringUtils;
use Doctrine\ORM\EntityManagerInterface;
use Flagception\Manager\FeatureManagerInterface;
use Omines\DataTablesBundle\DataTableFactory;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * Class PraticheController
 *
 * @package App\Controller
 * @Route("/pratiche")
 */
class PraticheController extends AbstractController
{

  /** @var InstanceService */
  private $instanceService;

  /** @var PraticaStatusService */
  private $praticaStatusService;

  /** @var ModuloPdfBuilderService */
  private $pdfBuilderService;

  /** @var ExpressionValidator */
  private $expressionValidator;

  /** @var LoggerInterface */
  private $logger;

  /** @var TranslatorInterface */
  private $translator;

  /** @var RouterInterface */
  private $router;

  /** @var FeatureManagerInterface */
  private $featureManager;
  /**
   * @var DataTableFactory
   */
  private $dataTableFactory;
  /**
   * @var PraticaManager
   */
  private $praticaManager;
  /**
   * @var FormServerApiAdapterService
   */
  private $formServerService;
  /**
   * @var EntityManagerInterface
   */
  private $entityManager;
  /**
   * @var BreadcrumbsService
   */
  private $breadcrumbsService;
  /**
   * @var ServizioHandlerRegistry
   */
  private $servizioHandlerRegistry;
  /**
   * @var MessageManager
   */
  private $messageManager;
  /**
   * @var ServiceManager
   */
  private $serviceManager;

  /**
   * @param InstanceService $instanceService
   * @param PraticaStatusService $praticaStatusService
   * @param ModuloPdfBuilderService $moduloPdfBuilderService
   * @param ExpressionValidator $validator
   * @param LoggerInterface $logger
   * @param TranslatorInterface $translator
   * @param RouterInterface $router
   * @param FeatureManagerInterface $featureManager
   * @param DataTableFactory $dataTableFactory
   * @param PraticaManager $praticaManager
   * @param FormServerApiAdapterService $formServerService
   * @param EntityManagerInterface $entityManager
   * @param BreadcrumbsService $breadcrumbsService
   * @param ServizioHandlerRegistry $servizioHandlerRegistry
   * @param MessageManager $messageManager
   * @param ServiceManager $serviceManager
   */
  public function __construct(
    InstanceService             $instanceService,
    PraticaStatusService        $praticaStatusService,
    ModuloPdfBuilderService     $moduloPdfBuilderService,
    ExpressionValidator         $validator,
    LoggerInterface             $logger,
    TranslatorInterface         $translator,
    RouterInterface             $router,
    FeatureManagerInterface     $featureManager,
    DataTableFactory            $dataTableFactory,
    PraticaManager              $praticaManager,
    FormServerApiAdapterService $formServerService,
    EntityManagerInterface      $entityManager,
    BreadcrumbsService          $breadcrumbsService,
    ServizioHandlerRegistry     $servizioHandlerRegistry,
    MessageManager              $messageManager,
    ServiceManager              $serviceManager
  )
  {
    $this->instanceService = $instanceService;
    $this->praticaStatusService = $praticaStatusService;
    $this->pdfBuilderService = $moduloPdfBuilderService;
    $this->expressionValidator = $validator;
    $this->logger = $logger;
    $this->translator = $translator;
    $this->router = $router;
    $this->featureManager = $featureManager;
    $this->dataTableFactory = $dataTableFactory;
    $this->praticaManager = $praticaManager;
    $this->formServerService = $formServerService;
    $this->entityManager = $entityManager;
    $this->breadcrumbsService = $breadcrumbsService;
    $this->servizioHandlerRegistry = $servizioHandlerRegistry;
    $this->messageManager = $messageManager;
    $this->serviceManager = $serviceManager;
  }

  /**
   * @Route("/", name="pratiche")
   *
   * @return Response
   */
  public function indexAction()
  {
    $this->breadcrumbsService->getBreadcrumbs()->addRouteItem($this->translator->trans('nav.pratiche'), 'pratiche');
    /** @var CPSUser $user */
    $user = $this->getUser();
    /** @var PraticaRepository $repo */
    $repo = $this->entityManager->getRepository('App\Entity\Pratica');
    $pratiche = $repo->count(
      array('user' => $user)
    );
    $pratichePending = $repo->findEvidencePraticaForUser($user);

    if (empty($pratichePending)) {
      $pratichePending = $repo->findPendingPraticaForUser($user);
    }

    $pending = [];
    foreach ($pratichePending as $p) {
      if (!isset($pending[$p->getServizio()->getSlug()])) {
        $pending[$p->getServizio()->getSlug()]['name'] = $p->getServizio()->getName();
        $pending[$p->getServizio()->getSlug()]['slug'] = $p->getServizio()->getSlug();
        $pending[$p->getServizio()->getSlug()]['applications'][] = $p;
      } else {
        if (count($pending[$p->getServizio()->getSlug()]) < 7) {
          $pending[$p->getServizio()->getSlug()]['applications'][] = $p;
        }
      }
    }

    return $this->render('Pratiche/index.html.twig', [
      'user' => $user,
      'pratiche' => $pratiche,
      'title' => 'lista_pratiche',
      'pending' => $pending,
    ]);
  }

  /**
   * @Route("/list", name="pratiche_list")
   * @Method({"GET", "POST"})
   * @param Request $request
   * @return Response
   */
  public function listAction(Request $request)
  {
    $this->breadcrumbsService->getBreadcrumbs()->addRouteItem('nav.pratiche', 'pratiche');
    $this->breadcrumbsService->getBreadcrumbs()->addRouteItem('breadcrumbs.list', 'pratiche_list');
    /** @var CPSUser $user */
    $user = $this->getUser();
    $criteria = [
      'user' => $user
    ];
    $serviceSlug = $request->query->get('service', false);
    if ($serviceSlug) {
      $serviceRepo = $this->entityManager->getRepository('App\Entity\Servizio');
      $service = $serviceRepo->findOneBy(['slug' => $serviceSlug]);
      if ($service instanceof Servizio) {
        $criteria ['servizio'] = $service;
      }
    }

    /** @var PraticaRepository $repo */
    $repo = $this->entityManager->getRepository('App\Entity\Pratica');
    $pratiche = $repo->findBy(
      $criteria,
      ['latestStatusChangeTimestamp' => 'DESC']
    );

    $services = [];
    $applications = [
      'draft' => [],
      'pending' => [],
      'completed' => [],
      'cancelled' => [],
      'integration' => [],
      'payment_pending' => [],
      'withdrawn' => [],
    ];

    /** @var Pratica $p */
    foreach ($pratiche as $p) {

      if (!isset($services[$p->getServizio()->getSlug()])) {
        $services[$p->getServizio()->getSlug()] = $p->getServizio()->getName();
      }

      if ($p->getStatus() == Pratica::STATUS_DRAFT) {
        $applications['draft'][] = $p;
      } elseif (in_array($p->getStatus(), [
        Pratica::STATUS_PRE_SUBMIT, Pratica::STATUS_SUBMITTED, Pratica::STATUS_REGISTERED, Pratica::STATUS_PENDING, Pratica::STATUS_PENDING_AFTER_INTEGRATION,
        Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE, Pratica::STATUS_REQUEST_INTEGRATION, Pratica::STATUS_REGISTERED_AFTER_INTEGRATION, Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE])) {
        $applications['pending'][] = $p;
      } elseif ($p->getStatus() == Pratica::STATUS_COMPLETE) {
        $applications['completed'][] = $p;
      } elseif ($p->getStatus() == Pratica::STATUS_CANCELLED) {
        $applications['cancelled'][] = $p;
      } elseif (in_array($p->getStatus(), [Pratica::STATUS_DRAFT_FOR_INTEGRATION, Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION])) {
        $applications['integration'][] = $p;
      } elseif (in_array($p->getStatus(), [Pratica::STATUS_PAYMENT_PENDING, Pratica::STATUS_PAYMENT_OUTCOME_PENDING])) {
        $applications['payment_pending'][] = $p;
      } elseif ($p->getStatus() == Pratica::STATUS_WITHDRAW) {
        $applications['withdrawn'][] = $p;
      }
    }

    $praticheRelated = $repo->findRelatedPraticaForUser($user);
    if (count($praticheRelated) > 0) {
      $applications['related'] = $praticheRelated;
    }

    foreach ($applications as $k => $v) {
      if (empty($v)) {
        unset($applications[$k]);
      }
    }

    return $this->render('Pratiche/list.html.twig', [
      'user' => $user,
      'title' => 'lista_pratiche',
      'tab_pratiche' => $applications,
      'service_slug' => $serviceSlug,
      'services' => $services
    ]);
  }

  /**
   * @Route("/{servizio}/draft", name="pratiche_list_draft")
   * @ParamConverter("servizio", class="App\Entity\Servizio", options={"mapping": {"servizio": "slug"}})
   * @param Servizio $servizio
   *
   * @return Response
   */
  public function listDraftByServiceAction(Servizio $servizio)
  {
    $user = $this->getUser();
    $repo = $this->entityManager->getRepository('App\Entity\Pratica');
    $pratiche = $repo->findBy(
      array(
        'user' => $user,
        'servizio' => $servizio,
        'status' => [
          Pratica::STATUS_DRAFT,
          Pratica::STATUS_DRAFT_FOR_INTEGRATION,
        ],
      ),
      array('creationTime' => 'ASC')
    );

    return $this->render('Pratiche/listDraftByService.html.twig', [
      'user' => $user,
      'pratiche' => $pratiche,
      'title' => 'bozze_servizio',
      'msg' => array(
        'type' => 'warning',
        'text' => 'msg_bozze_servizio',
      ),
    ]);
  }

  /**
   * @Route("/compila/{pratica}", name="pratiche_compila")
   * @ParamConverter("pratica", class="App\Entity\Pratica")
   * @param Request $request
   * @param Pratica $pratica
   *
   * @return Response
   */
  public function compilaAction(Request $request, Pratica $pratica, PraticaFlowRegistry $praticaFlowRegistry)
  {

    if ($pratica->getStatus() !== Pratica::STATUS_DRAFT_FOR_INTEGRATION
      && $pratica->getStatus() !== Pratica::STATUS_DRAFT
      && $pratica->getStatus() !== Pratica::STATUS_PAYMENT_PENDING) {
      return $this->redirectToRoute(
        'pratiche_show',
        ['pratica' => $pratica->getId()]
      );
    }

    $handler = $this->servizioHandlerRegistry->getByName($pratica->getServizio()->getHandler());
    try {
      $handler->canAccess($pratica->getServizio());
    } catch (ForbiddenAccessException $e) {
      $this->addFlash('warning', $this->translator->trans($e->getMessage(), $e->getParameters()));

      return $this->redirectToRoute('pratiche');
    }

    $user = $this->getUser();
    $this->denyAccessUnlessGranted(ApplicationVoter::COMPILE, $pratica, "User can not compile application {$pratica->getId()}");

    $this->breadcrumbsService->getBreadcrumbs()->addRouteItem($pratica->getServizio()->getName(), "servizi_show", ['slug' => $pratica->getServizio()->getSlug(),]);
    $this->breadcrumbsService->getBreadcrumbs()->addItem('breadcrumbs.compile');

    /** @var PraticaFlow $praticaFlowService */
    $praticaFlowService = $praticaFlowRegistry->getByName($pratica->getServizio()->getPraticaFlowServiceName());

    if ($pratica->getServizio()->isPaymentRequired()) {
      $praticaFlowService->setPaymentRequired(true);
    }

    $pratica->setLocale($request->getLocale());

    $praticaFlowService->setInstanceKey($user->getId());

    $praticaFlowService->bind($pratica);

    if ($pratica->getInstanceId() == null) {
      $pratica->setInstanceId($praticaFlowService->getInstanceId());
    }

    $form = $praticaFlowService->createForm();
    if ($praticaFlowService->isValid($form)) {

      $currentStep = $praticaFlowService->getCurrentStepNumber();

      $praticaFlowService->saveCurrentStepData($form);
      $pratica->setLastCompiledStep($currentStep);

      if ($praticaFlowService->nextStep()) {

        $this->entityManager->flush();
        $form = $praticaFlowService->createForm();

      } else {

        $pratica->setLocale($request->getLocale());
        $pratica->setGeneratedSubject();

        $this->entityManager->persist($pratica);
        $this->entityManager->flush();
        $praticaFlowService->onFlowCompleted($pratica);

        $this->logger->info(
          LogConstants::PRATICA_UPDATED,
          ['id' => $pratica->getId(), 'pratica' => $pratica]
        );

        $praticaFlowService->getDataManager()->drop($praticaFlowService);
        $praticaFlowService->reset();

        return $this->redirectToRoute(
          'pratiche_show',
          ['pratica' => $pratica->getId()]
        );
      }
    }

    $lastIntegrationMessage = null;

    $template = 'Pratiche/compila.html.twig';
    if ($pratica->getServizio()->isLegacy()) {
      $template = 'Pratiche/compileLegacy.html.twig';
      $repo = $this->entityManager->getRepository('App\Entity\Pratica');
      $requestIntegrationMessages = $repo->findStatusMessagesByStatus($pratica, Pratica::STATUS_REQUEST_INTEGRATION);
      $lastIntegrationMessage = end($requestIntegrationMessages);
    }

    return $this->render($template, [
      'form' => $form->createView(),
      'pratica' => $praticaFlowService->getFormData(),
      'integration_request_message' => $lastIntegrationMessage,
      'flow' => $praticaFlowService,
      'formserver_url' => $this->getParameter('formserver_public_url'),
      'user' => $user
    ]);

  }

  /**
   * @Route("/draft/{pratica}", name="pratiche_draft")
   * @ParamConverter("pratica", class="App\Entity\Pratica")
   * @param Request $request
   * @param Pratica $pratica
   *
   * @return Response
   */
  public function draftAction(Request $request, Pratica $pratica)
  {
    if ($pratica->getStatus() !== Pratica::STATUS_DRAFT) {
      return new JsonResponse(['status' => 'Application status is not valid'], Response::HTTP_BAD_REQUEST);
    }
    $service = $pratica->getServizio();
    $schema = null;
    $result = $this->formServerService->getFormSchema($service->getFormIoId());
    if ($result['status'] == 'success') {
      $schema = $result['schema'];
    }

    $flatSchema = $this->praticaManager->arrayFlat($schema, true);
    $cleanedData = StringUtils::cleanData($request->request->all());
    $flatData = $this->praticaManager->arrayFlat($cleanedData);

    $data = [
      'data' => $cleanedData,
      'flattened' => $flatData,
      'schema' => $flatSchema,
    ];

    try {
      $this->praticaManager->validateDematerializedData($data, $pratica);
      $pratica->setDematerializedForms($data);
      $this->entityManager->persist($pratica);
      $this->entityManager->flush();

      return new JsonResponse(['status' => 'ok']);
    } catch (ValidatorException $e) {
      $this->logger->error("Received invalid dematerialized data for application {$pratica->getId()}: {$e->getMessage()}");
      return new JsonResponse(['status' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
    } catch (\Exception $e) {
      $this->logger->error("An error occurred while saving application {$pratica->getId()}: {$e->getMessage()}");
      return new JsonResponse(['status' => 'error'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

  }

  /**
   * @Route("/{pratica}/calendar_event/{type}", name="pratiche_get_meeting_event")
   * @ParamConverter("pratica", class="App\Entity\Pratica")
   * @param Pratica $pratica
   * @param string $type
   * @return Response
   */
  public function downloadPraticaMeetingEventAction(Request $request, Pratica $pratica, ?string $type) 
  {
    $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $pratica, "User can not read application {$pratica->getId()}");

    try {
      $calendarEvent = new iCalUtils($pratica);
      return $calendarEvent->getMeetingEvent($type);
    } catch (\Exception $e) {
      $this->logger->error("An error occurred while trying to download the calendar event for the application {$pratica->getId()}: {$e->getMessage()}");
      return new Response('error', Response::HTTP_INTERNAL_SERVER_ERROR);
    } 
  }

  /**
   * @Route("/{pratica}", name="pratiche_show")
   * @ParamConverter("pratica", class="App\Entity\Pratica")
   * @param Pratica $pratica
   *
   * @return Response
   */
  public function showAction(Request $request, Pratica $pratica)
  {
    if ($pratica->getStatus() == Pratica::STATUS_DRAFT) {
      return $this->redirectToRoute(
        'pratiche_compila', 
        ['pratica' => $pratica->getId()]
      );
    }

    /** @var CPSUser $user */
    $user = $this->getUser();
    $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $pratica, "User can not read application {$pratica->getId()}");
    $resumeURI = $request->getUri();

    $this->breadcrumbsService->getBreadcrumbs()->addRouteItem($this->translator->trans('nav.pratiche'), 'pratiche');
    $this->breadcrumbsService->getBreadcrumbs()->addItem($pratica->getServizio()->getName());
    
    $result = [
      'pratica' => $pratica,
      'user' => $user,
      'formserver_url' => $this->getParameter('formserver_public_url'),
      'can_compile' => $this->isGranted(ApplicationVoter::COMPILE, $pratica),
      'can_withdraw' => $this->isGranted(ApplicationVoter::WITHDRAW, $pratica),
      'servizio' => $pratica->getServizio(),
      'servizi_correlati' => $this->serviceManager->getRelatedServices($pratica->getServizio())
    ];

    if ($pratica instanceof GiscomPratica) {
      $allegati = [];
      $attachments = $pratica->getAllegati();
      if (count($attachments) > 0) {

        /** @var Allegato $a */
        foreach ($attachments as $a) {
          $allegati[$a->getId()] = [
            'numero_protocollo' => $a->getNumeroProtocollo(),
            'id_documento_protocollo' => $a->getIdDocumentoProtocollo(),
            'description' => $a->getDescription()
          ];
        }
      }
      $result['allegati'] = $allegati;
    }

    $template = 'Pratiche/show.html.twig';
    if ($pratica->getServizio()->isLegacy()) {
      $template = 'Pratiche/showLegacy.html.twig';
    }

    return $this->render($template, $result);
  }

  /**
   * @Route("/{pratica}/detail", name="pratica_show_detail")
   * @ParamConverter("pratica", class="App\Entity\Pratica")
   * @param Pratica $pratica
   *
   * @return Response
   */
  public function detailAction(Request $request, Pratica $pratica)
  {

    if ($pratica instanceof GiscomPratica) {
      return $this->redirectToRoute('pratiche_show', ['pratica' => $pratica]);
    }

    if (!$this->featureManager->isActive('feature_application_detail')) {
      return $this->redirectToRoute('pratiche_show', ['pratica' => $pratica]);
    }

    /** @var CPSUser $user */
    $user = $this->getUser();
    $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $pratica, "User can not read application {$pratica->getId()}");
    $tab = $request->query->get('tab', false);

    $this->breadcrumbsService->getBreadcrumbs()->addRouteItem($this->translator->trans('nav.pratiche'), 'pratiche');
    $this->breadcrumbsService->getBreadcrumbs()->addItem($pratica->getServizio()->getName());

    $attachments = $this->entityManager->getRepository('App\Entity\Pratica')->getMessageAttachments(['visibility' => Message::VISIBILITY_APPLICANT, 'author' => $pratica->getUser()->getId()], $pratica);

    $message = new Message();
    $message->setApplication($pratica);
    $message->setAuthor($user);
    $messageForm = $this->createForm('App\Form\ApplicationMessageType', $message);
    $messageForm->handleRequest($request);

    if ($messageForm->isSubmitted() && $messageForm->isValid()) {
      /** @var Message $message */
      $message = $messageForm->getData();

      $callToActions = [
        ['label' => 'view', 'link' => $this->generateUrl('operatori_show_pratica', ['pratica' => $pratica, 'tab' => 'note'], UrlGeneratorInterface::ABSOLUTE_URL)],
        ['label' => 'reply', 'link' => $this->generateUrl('operatori_show_pratica', ['pratica' => $pratica, 'tab' => 'note'], UrlGeneratorInterface::ABSOLUTE_URL)],
      ];

      $message->setProtocolRequired(false);
      $message->setVisibility(Message::VISIBILITY_APPLICANT);
      $message->setCallToAction($callToActions);

      $this->messageManager->save($message);
      return $this->redirectToRoute('pratica_show_detail', ['pratica' => $pratica, 'tab' => 'note']);
    }

    $repository = $this->entityManager->getRepository('App\Entity\Pratica');
    $praticheRecenti = $repository->findRecentlySubmittedPraticheByUser($pratica, $user, 5);

    // Recupero l'id del messaggio associato all'ultimo cambio di stato di richiesta integrazione
    $applicationRepo = $this->entityManager->getRepository('App\Entity\Pratica');
    $messages = $applicationRepo->findStatusMessagesByStatus($pratica, Pratica::STATUS_REQUEST_INTEGRATION);
    $lastIntegrationMessage = end($messages);

    $result = [
      'pratiche_recenti' => $praticheRecenti,
      'applications_in_folder' => $repository->getApplicationsInFolder($pratica),
      'attachments_count' => $this->praticaManager->countAttachments($pratica),
      'messageAttachments' => $attachments,
      'messageForm' => $messageForm->createView(),
      'tab' => $tab,
      'pratica' => $pratica,
      'user' => $user,
      'formserver_url' => $this->getParameter('formserver_public_url'),
      'can_compile' => $this->isGranted(ApplicationVoter::COMPILE, $pratica),
      'can_withdraw' => $this->isGranted(ApplicationVoter::WITHDRAW, $pratica),
      'meetings' => $repository->findOrderedMeetings($pratica),
      'module_files' => $this->praticaManager->getGroupedModuleFiles($pratica),
      'last_owner_message' => $repository->getLastMessageByApplicationOwner($pratica),
      'integration_request_message' => $lastIntegrationMessage
    ];

    if ($pratica instanceof GiscomPratica) {
      $allegati = [];
      $attachments = $pratica->getAllegati();
      if (count($attachments) > 0) {

        /** @var Allegato $a */
        foreach ($attachments as $a) {
          $allegati[$a->getId()] = [
            'numero_protocollo' => $a->getNumeroProtocollo(),
            'id_documento_protocollo' => $a->getIdDocumentoProtocollo(),
            'description' => $a->getDescription()
          ];
        }
      }
      $result['allegati'] = $allegati;
    }

    return $this->render('Pratiche/detail.html.twig', $result);
  }

  /**
   * @Route("/{pratica}/withdraw", name="pratiche_withdraw")
   * @ParamConverter("pratica", class="App\Entity\Pratica")
   * @param Request $request
   * @param Pratica $pratica
   *
   * @return array|RedirectResponse
   * @throws \Exception
   */
  public function withdrawAction(Request $request, Pratica $pratica)
  {
    if ($this->isGranted(ApplicationVoter::WITHDRAW, $pratica)) {
      $withdrawAttachment = $this->pdfBuilderService->createWithdrawForPratica($pratica);
      $pratica->addAllegato($withdrawAttachment);
      $this->praticaStatusService->setNewStatus(
        $pratica,
        Pratica::STATUS_WITHDRAW
      );

      $this->addFlash('success', $this->translator->trans('operatori.pratica_annullata', ['%id%' => $pratica->getId()]));
      return $this->redirectToRoute('pratiche_list');
    }

    return $this->redirectToRoute(
      'pratica_show_detail', ['pratica' => $pratica->getId()]
    );
  }

  /**
   * @Route("/{pratica}/payment-callback", name="pratiche_payment_callback")
   * @ParamConverter("pratica", class="App\Entity\Pratica")
   * @param Request $request
   * @param Pratica $pratica
   *
   * @return array|RedirectResponse
   * @throws \Exception
   */
  public function paymentCallbackAction(Request $request, Pratica $pratica)
  {
    $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $pratica, "User can not read application {$pratica->getId()}");
    $outcome = $request->get('esito');

    if ($outcome == 'OK') {
      $this->praticaStatusService->setNewStatus(
        $pratica,
        Pratica::STATUS_PAYMENT_OUTCOME_PENDING
      );
    }

    return $this->redirectToRoute(
      'pratiche_show',
      [
        'pratica' => $pratica,
      ]
    );

  }

  /**
   * @Route("/{pratica}/delete", name="pratiche_delete")
   * @ParamConverter("pratica", class="App\Entity\Pratica")
   * @param Request $request
   * @param Pratica $pratica
   *
   * @return RedirectResponse
   */
  public function deleteAction(Request $request, Pratica $pratica)
  {
    $this->denyAccessUnlessGranted(ApplicationVoter::DELETE, $pratica, "User can not delete application {$pratica->getId()}");
    if ($pratica->getStatus() != Pratica::STATUS_DRAFT) {
      throw new UnauthorizedHttpException("Pratica can't be deleted, not in draft status");
    }

    $this->entityManager->remove($pratica);
    $this->entityManager->flush();


    return $this->redirectToRoute('pratiche_list');
  }

  /**
   * @Route("/formio/validate/{servizio}", name="formio_validate")
   * @ParamConverter("servizio", class="App\Entity\Servizio", options={"mapping": {"servizio": "slug"}})
   *
   * @param Request $request
   * @param Servizio $servizio
   *
   * @return JsonResponse
   */
  public function formioValidateAction(Request $request, Servizio $servizio)
  {
    $validator = $this->expressionValidator;

    $errors = $validator->validateData(
      $servizio,
      $request->getContent()
    );

    $response = ['status' => 'OK', 'errors' => null];
    if (!empty($errors)) {
      $response = ['status' => 'KO', 'errors' => $errors];
    }

    return JsonResponse::create($response, Response::HTTP_OK);
  }

  /**
   * @Route("/{pratica}/pdf", name="pratiche_show_pdf")
   * @ParamConverter("pratica", class="App\Entity\Pratica")
   * @param Request $request
   * @param Pratica $pratica
   *
   * @return Response
   * @throws \Exception
   */
  public function showPdfAction(Pratica $pratica): Response
  {
    $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $pratica, "User can not read application {$pratica->getId()}");
    $fileContent = $this->pdfBuilderService->renderForPratica($pratica, true);

    // Provide a name for your file with extension
    $filename = time() . '.pdf';

    // Return a response with a specific content
    $response = new Response($fileContent);

    // Create the disposition of the file
    $disposition = $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $filename
    );

    // Set the content disposition
    $response->headers->set('Content-Disposition', $disposition);

    // Dispatch request
    return $response;
  }
}

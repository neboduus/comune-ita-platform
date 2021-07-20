<?php

namespace AppBundle\Controller\Ui\Frontend;

use AppBundle\DataTable\PraticaTableType;
use AppBundle\DataTable\ScheduledActionTableType;
use AppBundle\Entity\Allegato;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Ente;
use AppBundle\Entity\GiscomPratica;
use AppBundle\Entity\Message;
use AppBundle\Entity\Nota;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\PraticaRepository;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use AppBundle\Form\Base\MessageType;
use AppBundle\Form\Base\PraticaFlow;
use AppBundle\FormIO\ExpressionValidator;
use AppBundle\Handlers\Servizio\ForbiddenAccessException;
use AppBundle\Handlers\Servizio\ServizioHandlerRegistry;
use AppBundle\Logging\LogConstants;
use AppBundle\Model\CallToAction;
use AppBundle\Security\Voters\ApplicationVoter;
use AppBundle\Services\FormServerApiAdapterService;
use AppBundle\Services\InstanceService;
use AppBundle\Services\MailerService;
use AppBundle\Services\Manager\PraticaManager;
use AppBundle\Services\ModuloPdfBuilderService;
use AppBundle\Services\PraticaStatusService;
use Doctrine\ORM\EntityManagerInterface;
use Flagception\Manager\FeatureManagerInterface;
use Omines\DataTablesBundle\Controller\DataTablesTrait;
use Omines\DataTablesBundle\DataTableFactory;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class PraticheController
 *
 * @package AppBundle\Controller
 * @Route("/pratiche")
 */
class PraticheController extends Controller
{

  use DataTablesTrait;

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

  /** @var MailerService */
  private $mailer;

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
   * PraticheController constructor.
   * @param InstanceService $instanceService
   * @param PraticaStatusService $praticaStatusService
   * @param ModuloPdfBuilderService $moduloPdfBuilderService
   * @param ExpressionValidator $validator
   * @param LoggerInterface $logger
   * @param TranslatorInterface $translator
   * @param RouterInterface $router
   * @param MailerService $mailer
   * @param FeatureManagerInterface $featureManager
   * @param DataTableFactory $dataTableFactory
   * @param PraticaManager $praticaManager
   * @param FormServerApiAdapterService $formServerService
   * @param EntityManagerInterface $entityManager
   */
  public function __construct(
    InstanceService $instanceService,
    PraticaStatusService $praticaStatusService,
    ModuloPdfBuilderService $moduloPdfBuilderService,
    ExpressionValidator $validator,
    LoggerInterface $logger,
    TranslatorInterface $translator,
    RouterInterface $router,
    MailerService $mailer,
    FeatureManagerInterface $featureManager,
    DataTableFactory $dataTableFactory,
    PraticaManager $praticaManager,
    FormServerApiAdapterService $formServerService,
    EntityManagerInterface $entityManager
  )
  {
    $this->instanceService = $instanceService;
    $this->praticaStatusService = $praticaStatusService;
    $this->pdfBuilderService = $moduloPdfBuilderService;
    $this->expressionValidator = $validator;
    $this->logger = $logger;
    $this->translator = $translator;
    $this->router = $router;
    $this->mailer = $mailer;
    $this->featureManager = $featureManager;
    $this->dataTableFactory = $dataTableFactory;
    $this->praticaManager = $praticaManager;
    $this->formServerService = $formServerService;
    $this->entityManager = $entityManager;
  }

  /**
   * @Route("/", name="pratiche")
   *
   * @return Response
   */
  public function indexAction()
  {
    /** @var CPSUser $user */
    $user = $this->getUser();
    /** @var PraticaRepository $repo */
    $repo = $this->getDoctrine()->getRepository('AppBundle:Pratica');
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
        $pending[$p->getServizio()->getSlug()]['applications'][]= $p;
      } else {
        if (count($pending[$p->getServizio()->getSlug()]) < 7) {
          $pending[$p->getServizio()->getSlug()]['applications'][]= $p;
        }
      }
    }

    return $this->render( '@App/Pratiche/index.html.twig', [
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
    /** @var CPSUser $user */
    $user = $this->getUser();
    $criteria = [
      'user' => $user
    ];
    $serviceSlug = $request->query->get('service', false);
    if ($serviceSlug) {
      $serviceRepo = $this->getDoctrine()->getRepository('AppBundle:Servizio');
      $service = $serviceRepo->findOneBy(['slug' => $serviceSlug]);
      if ($service instanceof Servizio) {
        $criteria ['servizio'] = $service;
      }
    }

    /** @var PraticaRepository $repo */
    $repo = $this->getDoctrine()->getRepository('AppBundle:Pratica');
    $pratiche = $repo->findBy(
      $criteria,
      ['latestStatusChangeTimestamp' => 'DESC',]
    );

    $services = $applications = [];

    /** @var Pratica $p */
    foreach ($pratiche as $p) {

      if (!isset($services[$p->getServizio()->getSlug()])) {
        $services[$p->getServizio()->getSlug()] = $p->getServizio()->getName();
      }

      if ($p->getStatus() == Pratica::STATUS_DRAFT) {
        $applications['draft'][]= $p;
      } elseif (in_array($p->getStatus(), [
          Pratica::STATUS_PRE_SUBMIT, Pratica::STATUS_SUBMITTED, Pratica::STATUS_REGISTERED, Pratica::STATUS_PENDING, Pratica::STATUS_PENDING_AFTER_INTEGRATION,
          Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE, Pratica::STATUS_REQUEST_INTEGRATION, Pratica::STATUS_REGISTERED_AFTER_INTEGRATION, Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE])) {
        $applications['pending'][]= $p;
      } elseif ($p->getStatus() == Pratica::STATUS_COMPLETE) {
        $applications['completed'][]= $p;
      } elseif ($p->getStatus() == Pratica::STATUS_CANCELLED) {
        $applications['cancelled'][]= $p;
      } elseif (in_array($p->getStatus(), [Pratica::STATUS_DRAFT_FOR_INTEGRATION, Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION])) {
        $applications['integration'][]= $p;
      } elseif ($p->getStatus() == Pratica::STATUS_PAYMENT_PENDING) {
        $applications['payment_pending'][]= $p;
      } elseif ($p->getStatus() == Pratica::STATUS_WITHDRAW) {
        $applications['withdrawn'][]= $p;
      }
    }

    $praticheRelated = $repo->findRelatedPraticaForUser($user);
    if (count($praticheRelated) > 0) {
      $applications['related'] = $praticheRelated;
    }

    return $this->render( '@App/Pratiche/list.html.twig', [
      'user' => $user,
      'title' => 'lista_pratiche',
      'tab_pratiche' => $applications,
      'service_slug' => $serviceSlug,
      'services' => $services
    ]);
  }

  /**
   * @Route("/{servizio}/new", name="pratiche_new")
   * @ParamConverter("servizio", class="AppBundle:Servizio", options={"mapping": {"servizio": "slug"}})
   *
   * @param Request $request
   * @param Servizio $servizio
   *
   * @return Response
   */
  public function newAction(Request $request, Servizio $servizio)
  {
    $handler = $this->get(ServizioHandlerRegistry::class)->getByName($servizio->getHandler());

    $ente = $this->instanceService->getCurrentInstance();

    if (!$ente instanceof Ente) {
      $this->logger->info(LogConstants::PRATICA_WRONG_ENTE_REQUESTED, ['headers' => $request->headers]);
      throw new \InvalidArgumentException(LogConstants::PRATICA_WRONG_ENTE_REQUESTED);
    }

    try {
      $handler->canAccess($servizio, $ente);
    } catch (ForbiddenAccessException $e) {
      $this->addFlash('warning', $this->translator->trans($e->getMessage(), $e->getParameters()));

      return $this->redirectToRoute('servizi_list');
    }

    try {

      return $handler->execute($servizio, $ente);
    } catch (\Exception $e) {
      $this->logger->error($e->getMessage(), ['servizio' => $servizio->getSlug()]);

      return $this->render(
        '@App/Servizi/serviziFeedback.html.twig',
        array(
          'servizio' => $servizio,
          'status' => 'danger',
          'message' => $handler->getErrorMessage(),
          'message_detail' => $e->getMessage(),
        )
      );
    }
  }

  /**
   * @Route("/{servizio}/draft", name="pratiche_list_draft")
   * @ParamConverter("servizio", class="AppBundle:Servizio", options={"mapping": {"servizio": "slug"}})
   * @param Servizio $servizio
   *
   * @return Response
   */
  public function listDraftByServiceAction(Servizio $servizio)
  {
    $user = $this->getUser();
    $repo = $this->getDoctrine()->getRepository('AppBundle:Pratica');
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

    return $this->render( '@App/Pratiche/listDraftByService.html.twig', [
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
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @param Pratica $pratica
   *
   * @return Response
   */
  public function compilaAction(Request $request, Pratica $pratica)
  {
    $em = $this->getDoctrine()->getManager();
    if ($pratica->getStatus() !== Pratica::STATUS_DRAFT_FOR_INTEGRATION
      && $pratica->getStatus() !== Pratica::STATUS_DRAFT
      && $pratica->getStatus() !== Pratica::STATUS_PAYMENT_PENDING) {
      return $this->redirectToRoute(
        'pratiche_show',
        ['pratica' => $pratica->getId()]
      );
    }

    $handler = $this->get(ServizioHandlerRegistry::class)->getByName($pratica->getServizio()->getHandler());
    try {
      $handler->canAccess($pratica->getServizio(), $pratica->getEnte());
    } catch (ForbiddenAccessException $e) {
      $this->addFlash('warning', $this->translator->trans($e->getMessage(), $e->getParameters()));

      return $this->redirectToRoute('pratiche');
    }

    $user = $this->getUser();
    $this->checkUserCanAccessPratica($pratica, $user);

    /** @var PraticaFlow $praticaFlowService */
    $praticaFlowService = $this->get($pratica->getServizio()->getPraticaFlowServiceName());

    if ($pratica->getServizio()->isPaymentRequired()) {
      $praticaFlowService->setPaymentRequired(true);
    }

    $praticaFlowService->setInstanceKey($user->getId());

    $praticaFlowService->bind($pratica);

    if ($pratica->getInstanceId() == null) {
      $pratica->setInstanceId($praticaFlowService->getInstanceId());
    }
    $resumeURI = $praticaFlowService->getResumeUrl($request);
    //$thread = $this->createThreadElementsForUserAndPratica($pratica, $user, $resumeURI);

    $form = $praticaFlowService->createForm();
    if ($praticaFlowService->isValid($form)) {

      $currentStep = $praticaFlowService->getCurrentStepNumber();

      $praticaFlowService->saveCurrentStepData($form);
      $pratica->setLastCompiledStep($currentStep);

      if ($praticaFlowService->nextStep()) {

        $em->flush();
        $form = $praticaFlowService->createForm();

        $resumeURI = $praticaFlowService->getResumeUrl($request);
        //$thread = $this->createThreadElementsForUserAndPratica($pratica, $user, $resumeURI);

      } else {

        $praticaFlowService->onFlowCompleted($pratica);

        $this->logger->info(
          LogConstants::PRATICA_UPDATED,
          ['id' => $pratica->getId(), 'pratica' => $pratica]
        );

        // $this->addFlash('feedback', $this->get('translator')->trans('pratica_ricevuta'));

        $praticaFlowService->getDataManager()->drop($praticaFlowService);
        $praticaFlowService->reset();

        return $this->redirectToRoute(
          'pratiche_show',
          ['pratica' => $pratica->getId()]
        );
      }
    }

    return $this->render( '@App/Pratiche/compila.html.twig',  [
      'form' => $form->createView(),
      'pratica' => $praticaFlowService->getFormData(),
      'flow' => $praticaFlowService,
      'formserver_url' => $this->getParameter('formserver_public_url'),
      'user' => $user
      //'threads' => $thread,
    ]);
  }

  /**
   * @Route("/draft/{pratica}", name="pratiche_draft")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @param Request $request
   * @param Pratica $pratica
   *
   * @return Response
   */
  public function draftAction(Request $request, Pratica $pratica)
  {
    $service = $pratica->getServizio();
    $schema = null;
    $result = $this->formServerService->getFormSchema($service->getFormIoId());
    if ($result['status'] == 'success') {
      $schema = $result['schema'];
    }

    $flatSchema = $this->praticaManager->arrayFlat($schema, true);
    $flatData = $this->praticaManager->arrayFlat($request->request);

    $data = [
      'data' => $request->request->all(),
      'flattened' => $flatData,
      'schema' => $flatSchema,
    ];

    try {
      $pratica->setDematerializedForms($data);
      $this->entityManager->persist($pratica);
      $this->entityManager->flush();

      return new JsonResponse(['status' => 'ok']);

    } catch (\Exception $e) {
      return new JsonResponse(['status' => 'error'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

  }

  private function checkUserCanAccessPratica(Pratica $pratica, CPSUser $user)
  {
    $praticaUser = $pratica->getUser();
    $isTheOwner = $praticaUser->getId() === $user->getId();
    $cfs = $pratica->getRelatedCFs();
    if (!is_array($cfs)) {
      $cfs = [$cfs];
    }
    $isRelated = in_array($user->getCodiceFiscale(), $cfs);


    if (!$isTheOwner && !$isRelated) {
      throw new UnauthorizedHttpException("User can not read pratica {$pratica->getId()}");
    }
  }

  /**
   * @Route("/{pratica}", name="pratiche_show")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @param Pratica $pratica
   *
   * @return Response
   */
  public function showAction(Request $request, Pratica $pratica)
  {
    /** @var CPSUser $user */
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($pratica, $user);
    $resumeURI = $request->getUri();

    $canCompile = ($pratica->getStatus() == Pratica::STATUS_DRAFT || $pratica->getStatus() == Pratica::STATUS_DRAFT_FOR_INTEGRATION)
      && $pratica->getUser()->getId() == $user->getId();
    if ($canCompile) {
      $handler = $this->get(ServizioHandlerRegistry::class)->getByName($pratica->getServizio()->getHandler());
      try {
        $handler->canAccess($pratica->getServizio(), $pratica->getEnte());
      } catch (ForbiddenAccessException $e) {
        $canCompile = false;
      }
    }


    $result = [
      'pratica' => $pratica,
      'user' => $user,
      'formserver_url' => $this->getParameter('formserver_public_url'),
      'can_compile' => $canCompile,
      'can_withdraw' => $this->isGranted( ApplicationVoter::WITHDRAW, $pratica)
      //'threads' => $thread,
    ];

    if ($pratica instanceof GiscomPratica) {
      $allegati = [];
      $attachments = $pratica->getAllegati();
      if (count($attachments) > 0) {

        /** @var Allegato $a */
        foreach ($attachments as $a) {
          $allegati[$a->getId()] = [
            'numero_protocollo' => $a->getNumeroProtocollo(),
            'id_documento_protocollo'  => $a->getIdDocumentoProtocollo(),
            'description'  => $a->getDescription()
          ];
        }
      }
      $result['allegati'] = $allegati;
    }

    return $this->render( '@App/Pratiche/show.html.twig',  $result);
  }

  /**
   * @Route("/{pratica}/detail", name="pratica_show_detail")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @param Pratica $pratica
   *
   * @return Response
   */
  public function detailAction(Request $request, Pratica $pratica)
  {
    $translator = $this->translator;

    if ($pratica instanceof GiscomPratica) {
      return $this->redirectToRoute('pratiche_show', ['pratica' => $pratica]);
    }

    if (!$this->featureManager->isActive('feature_application_detail')) {
      return $this->redirectToRoute('pratiche_show', ['pratica' => $pratica]);
    }

    /** @var CPSUser $user */
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($pratica, $user);
    $tab = $request->query->get('tab', false);

    $attachments = $this->getDoctrine()->getRepository('AppBundle:Pratica')->getMessageAttachments(['visibility'=> Message::VISIBILITY_APPLICANT, 'author' => $pratica->getUser()->getId()], $pratica);

    $canCompile = ($pratica->getStatus() == Pratica::STATUS_DRAFT) && $pratica->getUser()->getId() == $user->getId();
    if ($canCompile) {
      $handler = $this->get(ServizioHandlerRegistry::class)->getByName($pratica->getServizio()->getHandler());
      try {
        $handler->canAccess($pratica->getServizio(), $pratica->getEnte());
      } catch (ForbiddenAccessException $e) {
        $canCompile = false;
      }
    }

    $message = new Message();
    $message->setApplication($pratica);
    $message->setAuthor($user);
    $messageForm = $this->createForm('AppBundle\Form\ApplicationMessageType', $message);
    $messageForm->handleRequest($request);

    if ($messageForm->isSubmitted() && $messageForm->isValid()) {
      /** @var Message $message */
      $message = $messageForm->getData();

      $callToActions = [
        ['label'=>'view', 'link'=>$this->generateUrl('operatori_show_pratica', ['pratica' => $pratica, 'tab'=>'note'], UrlGeneratorInterface::ABSOLUTE_URL)],
        ['label'=>'reply', 'link'=>$this->generateUrl('operatori_show_pratica', ['pratica' => $pratica, 'tab'=>'note'], UrlGeneratorInterface::ABSOLUTE_URL)],
      ];

      $message->setProtocolRequired(false);
      $message->setVisibility(Message::VISIBILITY_APPLICANT);
      $message->setCallToAction($callToActions);

      $em = $this->getDoctrine()->getManager();
      $em->persist($message);
      $em->flush();

      $this->logger->info(
        LogConstants::PRATICA_COMMENTED,
        [
          'pratica' => $pratica->getId(),
          'user' => $pratica->getUser()->getId()
        ]
      );

      // Todo: rendere asincrono l'invio delle email
      if ($pratica->getOperatore()) {
        $defaultSender = $this->getParameter('default_from_email_address');
        $instance = $this->instanceService->getCurrentInstance();
        /** @var OperatoreUser $userReceiver */
        $userReceiver = $message->getApplication()->getOperatore();
        $subject = $translator->trans('pratica.messaggi.oggetto', ['%pratica%' => $pratica]);
        $mess = $translator->trans('pratica.messaggi.messaggio', [
          '%message%' => $message->getMessage(),
          '%link%' => $this->router->generate('track_message', ['id'=>$message->getId()], UrlGeneratorInterface::ABSOLUTE_URL) . '?id='. $message->getId()]);
        $this->mailer->dispatchMail($defaultSender, $user->getFullName(),$userReceiver->getEmail(), $userReceiver->getFullName(), $mess, $subject, $instance, $message->getCallToAction());

        $message->setSentAt(time());
        $message->setEmail($userReceiver->getEmail());
        $em->persist($message);
        $em->flush();
      }


      return $this->redirectToRoute('pratica_show_detail', ['pratica' => $pratica, 'tab'=>'note']);
    }

    $repository = $this->getDoctrine()->getRepository('AppBundle:Pratica');
    $praticheRecenti = $repository->findRecentlySubmittedPraticheByUser($pratica, $user, 5);

    $result = [
      'pratiche_recenti' => $praticheRecenti,
      'applications_in_folder' => $repository->getApplicationsInFolder($pratica),
      'messageAttachments' => $attachments,
      'messageForm' => $messageForm->createView(),
      'tab' => $tab,
      'pratica' => $pratica,
      'user' => $user,
      'formserver_url' => $this->getParameter('formserver_public_url'),
      'can_compile' => $canCompile,
      'can_withdraw' => $this->isGranted( ApplicationVoter::WITHDRAW, $pratica),
      'meetings' => $repository->findOrderedMeetings($pratica),
      //'threads' => $thread,
    ];

    if ($pratica instanceof GiscomPratica) {
      $allegati = [];
      $attachments = $pratica->getAllegati();
      if (count($attachments) > 0) {

        /** @var Allegato $a */
        foreach ($attachments as $a) {
          $allegati[$a->getId()] = [
            'numero_protocollo' => $a->getNumeroProtocollo(),
            'id_documento_protocollo'  => $a->getIdDocumentoProtocollo(),
            'description'  => $a->getDescription()
          ];
        }
      }
      $result['allegati'] = $allegati;
    }

    return $this->render( '@App/Pratiche/detail.html.twig',  $result);
  }

  /**
   * @Route("/{pratica}/withdraw", name="pratiche_withdraw")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @param Request $request
   * @param Pratica $pratica
   *
   * @return array|RedirectResponse
   * @throws \Exception
   */
  public function withdrawAction(Request $request, Pratica $pratica)
  {
    /** @var CPSUser $user */
    $user = $this->getUser();
    if ($this->isGranted( ApplicationVoter::WITHDRAW, $pratica)) {
      $withdrawAttachment = $this->pdfBuilderService->createWithdrawForPratica($pratica);
      $pratica->addAllegato($withdrawAttachment);
      $this->praticaStatusService->setNewStatus(
        $pratica,
        Pratica::STATUS_WITHDRAW
      );
    }

    return $this->redirectToRoute(
      'pratiche_show', ['pratica' => $pratica->getId()]
    );
  }

  /**
   * @Route("/{pratica}/payment-callback", name="pratiche_payment_callback")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @param Request $request
   * @param Pratica $pratica
   *
   * @return array|RedirectResponse
   * @throws \Exception
   */
  public function paymentCallbackAction(Request $request, Pratica $pratica)
  {
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($pratica, $user);
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
   * @Route("/{pratica}/pdf", name="pratiche_show_pdf")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @param Request $request
   * @param Pratica $pratica
   *
   * @return BinaryFileResponse
   * @throws \Exception
   */
  public function showPdfAction(Request $request, Pratica $pratica)
  {
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($pratica, $user);
    $allegato = $this->pdfBuilderService->showForPratica($pratica);

    $fileName = $allegato->getOriginalFilename();
    if (substr($fileName, -3) != $allegato->getFile()->getExtension()) {
      $fileName .= '.'.$allegato->getFile()->getExtension();
    }

    return new BinaryFileResponse(
      $allegato->getFile()->getPath().'/'.$allegato->getFile()->getFilename(),
      200,
      [
        'Content-type' => 'application/octet-stream',
        'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
      ]
    );
  }

  /**
   * @Route("/{pratica}/delete", name="pratiche_delete")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @param Pratica $pratica
   *
   * @return RedirectResponse
   */
  public function deleteAction(Request $request, Pratica $pratica)
  {
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($pratica, $user);
    if ($pratica->getStatus() != Pratica::STATUS_DRAFT) {
      throw new UnauthorizedHttpException("Pratica can't be deleted, not in draft status");
    }

    $em = $this->getDoctrine()->getManager();
    $em->remove($pratica);
    $em->flush();


    return $this->redirectToRoute('pratiche');
  }

  /**
   * @Route("/{pratica}/protocollo", name="pratiche_show_protocolli")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @param Pratica $pratica
   *
   * @return Response
   */
  public function showProtocolliAction(Request $request, Pratica $pratica)
  {
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($pratica, $user);
    $resumeURI = $request->getUri();
    //$thread = $this->createThreadElementsForUserAndPratica($pratica, $user, $resumeURI);

    $allegati = [];
    foreach ($pratica->getNumeriProtocollo() as $protocollo) {
      $allegato = $this->getDoctrine()->getRepository('AppBundle:Allegato')->find($protocollo->id);
      if ($allegato instanceof Allegato) {
        $allegati[] = [
          'allegato' => $allegato,
          'tipo' => (new \ReflectionClass(get_class($allegato)))->getShortName(),
          'protocollo' => $protocollo->protocollo,
        ];
      }
    }

    return $this->render( '@App/Pratiche/showProtocolli.html.twig',  [
      'pratica' => $pratica,
      'allegati' => $allegati,
      'user' => $user,
      //'threads' => $thread,
    ]);
  }

  /**
   * @Route("/formio/validate/{servizio}", name="formio_validate")
   * @ParamConverter("servizio", class="AppBundle:Servizio", options={"mapping": {"servizio": "slug"}})
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
    if (!empty($errors)){
      $response = ['status' => 'KO', 'errors' => $errors];
    }

    return JsonResponse::create($response, Response::HTTP_OK);
  }
}

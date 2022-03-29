<?php

namespace AppBundle\Controller\Ui\Backend;

use AppBundle\Controller\Rest\ServicesAPIController;
use AppBundle\DataTable\ScheduledActionTableType;
use AppBundle\Dto\Service;
use AppBundle\Entity\AuditLog;
use AppBundle\Entity\Categoria;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Erogatore;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\ScheduledAction;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\Webhook;
use AppBundle\Form\Admin\ServiceFlow;
use AppBundle\Form\Admin\Servizio\CardDataType;
use AppBundle\Form\Admin\Servizio\FeedbackMessagesDataType;
use AppBundle\Form\Admin\Servizio\FormIOBuilderRenderType;
use AppBundle\Form\Admin\Servizio\FormIOI18nType;
use AppBundle\Form\Admin\Servizio\FormIOTemplateType;
use AppBundle\Form\Admin\Servizio\GeneralDataType;
use AppBundle\Form\Admin\Servizio\IntegrationsDataType;
use AppBundle\Form\Admin\Servizio\IOIntegrationDataType;
use AppBundle\Form\Admin\Servizio\PaymentDataType;
use AppBundle\Form\Admin\Servizio\ProtocolDataType;
use AppBundle\FormIO\SchemaFactoryInterface;
use AppBundle\Model\FlowStep;
use AppBundle\Services\FormServerApiAdapterService;
use AppBundle\Services\InstanceService;
use AppBundle\Services\MailerService;
use AppBundle\Services\Manager\ServiceManager;
use Doctrine\DBAL\DBALException;
use AppBundle\Services\IOService;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Util\TokenGenerator;
use GuzzleHttp\Client;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Controller\DataTablesTrait;
use Omines\DataTablesBundle\DataTableFactory;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;


/**
 * Class AdminController
 * @Route("/admin")
 */
class AdminController extends Controller
{
  use DataTablesTrait;

  /** @var InstanceService */
  private $instanceService;

  /** @var FormServerApiAdapterService */
  private $formServer;

  /**@var TokenGenerator */
  private $tokenGenerator;

  /** @var TranslatorInterface */
  private $translator;

  /** @var ServiceFlow */
  private $serviceFlow;

  /** @var SchemaFactoryInterface */
  private $schemaFactory;
  /**
   * @var IOService
   */
  private $ioService;
  /**
   * @var RouterInterface
   */
  private $router;
  /**
   * @var DataTableFactory
   */
  private $dataTableFactory;
  /**
   * @var LoggerInterface
   */
  private $logger;
  /**
   * @var ServiceManager
   */
  private $serviceManager;
  private $locales;
  /**
   * @var MailerInterface
   */
  private $mailer;


  /**
   * @param InstanceService $instanceService
   * @param FormServerApiAdapterService $formServer
   * @param TokenGenerator $tokenGenerator
   * @param TranslatorInterface $translator
   * @param ServiceFlow $serviceFlow
   * @param SchemaFactoryInterface $schemaFactory
   * @param IOService $ioService
   * @param RouterInterface $router
   * @param DataTableFactory $dataTableFactory
   * @param LoggerInterface $logger
   * @param ServiceManager $serviceManager
   * @param MailerInterface $mailer
   * @param $locales
   */
  public function __construct(
    InstanceService $instanceService,
    FormServerApiAdapterService $formServer,
    TokenGenerator $tokenGenerator,
    TranslatorInterface $translator,
    ServiceFlow $serviceFlow,
    SchemaFactoryInterface $schemaFactory,
    IOService $ioService,
    RouterInterface $router,
    DataTableFactory $dataTableFactory,
    LoggerInterface $logger,
    ServiceManager $serviceManager,
    MailerInterface $mailer,
    $locales
  )
  {
    $this->instanceService = $instanceService;
    $this->formServer = $formServer;
    $this->tokenGenerator = $tokenGenerator;
    $this->translator = $translator;
    $this->serviceFlow = $serviceFlow;
    $this->schemaFactory = $schemaFactory;
    $this->ioService = $ioService;
    $this->router = $router;
    $this->dataTableFactory = $dataTableFactory;
    $this->logger = $logger;
    $this->serviceManager = $serviceManager;
    $this->locales = explode('|', $locales);
    $this->mailer = $mailer;
  }


  /**
   * @Route("/", name="admin_index")
   * @param Request $request
   * @return Response
   */
  public function indexAction(Request $request)
  {
    return $this->render('@App/Admin/index.html.twig', [
      'user' => $this->getUser()
    ]);
  }

  /**
   * @Route("/ente", name="admin_edit_ente")
   * @param Request $request
   * @return Response|RedirectResponse
   */
  public function editEnteAction(Request $request)
  {

    $servizi = $this->instanceService->getServices();
    $services = [];
    $services ['all'] = 'Tutti';
    foreach ($servizi as $s) {
      $services[$s->getId()] = $s->getName();
    }

    $entityManager = $this->getDoctrine()->getManager();
    $ente = $this->instanceService->getCurrentInstance();
    $form = $this->createForm('AppBundle\Form\Admin\Ente\EnteType', $ente);
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
      $ente = $form->getData();

      $entityManager->persist($ente);
      $entityManager->flush();

      return $this->redirectToRoute('admin_edit_ente');
    }

    return $this->render('@App/Admin/editEnte.html.twig', [
      'user' => $this->getUser(),
      'ente' => $ente,
      'statuses' => Webhook::TRIGGERS,
      'services' => $services,
      'form' => $form->createView()
    ]);
  }


  /**
   * Lists all operatoreUser entities.
   * @Route("/operatore", name="admin_operatore_index")
   * @Method("GET")
   */
  public function indexOperatoreAction()
  {
    $em = $this->getDoctrine()->getManager();

    $operatoreUsers = $em->getRepository('AppBundle:OperatoreUser')->findAll();

    return $this->render('@App/Admin/indexOperatore.html.twig', [
      'user' => $this->getUser(),
      'operatoreUsers' => $operatoreUsers,
    ]);
  }

  /**
   * Creates a new operatoreUser entity.
   * @Route("/operatore/new", name="admin_operatore_new")
   * @Method({"GET", "POST"})
   * @param Request $request
   * @return Response
   */
  public function newOperatoreAction(Request $request)
  {
    $operatoreUser = new Operatoreuser();
    $form = $this->createForm('AppBundle\Form\OperatoreUserType', $operatoreUser);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $em = $this->getDoctrine()->getManager();
      $ente = $this->instanceService->getCurrentInstance();

      $operatoreUser
        ->setEnte($ente)
        ->setPlainPassword(md5(time()))
        ->setConfirmationToken($this->tokenGenerator->generateToken())
        ->setPasswordRequestedAt(new \DateTime())
        ->setEnabled(true);
      $em->persist($operatoreUser);
      $em->flush();

      $this->mailer->sendResettingEmailMessage($operatoreUser);

      $this->addFlash('feedback', 'Operatore creato con successo');
      return $this->redirectToRoute('admin_operatore_show', array('id' => $operatoreUser->getId()));
    }

    return $this->render('@App/Admin/editOperatore.html.twig', [
      'user' => $this->getUser(),
      'operatoreUser' => $operatoreUser,
      'form' => $form->createView(),
    ]);
  }

  /**
   * Finds and displays a operatoreUser entity.
   * @Route("/operatore/{id}", name="admin_operatore_show")
   * @Method("GET")
   */
  public function showOperatoreAction(OperatoreUser $operatoreUser)
  {
    if ($operatoreUser->getServiziAbilitati()->count() > 0) {
      $serviziAbilitati = $this->getDoctrine()
        ->getRepository(Servizio::class)
        ->findBy(['id' => $operatoreUser->getServiziAbilitati()->toArray()]);
    } else {
      $serviziAbilitati = [];
    }

    return $this->render('@App/Admin/showOperatore.html.twig', [
      'user' => $this->getUser(),
      'operatoreUser' => $operatoreUser,
      'servizi_abilitati' => $serviziAbilitati
    ]);
  }

  /**
   * Displays a form to edit an existing operatoreUser entity.
   * @Route("/operatore/{id}/edit", name="admin_operatore_edit")
   * @Method({"GET", "POST"})
   */
  public function editOperatoreAction(Request $request, OperatoreUser $operatoreUser)
  {
    $form = $this->createForm('AppBundle\Form\OperatoreUserType', $operatoreUser);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $this->getDoctrine()->getManager()->flush();

      return $this->redirectToRoute('admin_operatore_edit', array('id' => $operatoreUser->getId()));
    }

    return $this->render('@App/Admin/editOperatore.html.twig', [
      'user' => $this->getUser(),
      'operatoreUser' => $operatoreUser,
      'form' => $form->createView()
    ]);
  }

  /**
   * Send password reset hash to user.
   * @Route("/operatore/{id}/resetpassword", name="admin_operatore_reset_password")
   * @Method({"GET", "POST"})
   */
  public function resetPasswordOperatoreAction(Request $request, OperatoreUser $operatoreUser)
  {
    $em = $this->getDoctrine()->getManager();
    $operatoreUser
      ->setConfirmationToken($this->tokenGenerator->generateToken())
      ->setPasswordRequestedAt(new \DateTime());
    $em->persist($operatoreUser);
    $em->flush();

    $this->mailer->sendResettingEmailMessage($operatoreUser);

    return $this->redirectToRoute('admin_operatore_edit', array('id' => $operatoreUser->getId()));
  }

  /**
   * Deletes a operatoreUser entity.
   * @Route("/operatore/{id}/delete", name="admin_operatore_delete")
   * @Method({"GET", "POST", "DELETE"})
   */
  public function deleteOperatoreAction(Request $request, OperatoreUser $operatoreUser)
  {
    try {
      $em = $this->getDoctrine()->getManager();
      $em->remove($operatoreUser);
      $em->flush();
      $this->addFlash('feedback', 'Operatore eliminato correttamente');
      return $this->redirectToRoute('admin_operatore_index');

    } catch (ForeignKeyConstraintViolationException $exception) {
      $this->addFlash('warning', 'Impossibile eliminare l\'operatore, ci sono delle pratiche collegate.');
      return $this->redirectToRoute('admin_servizio_index');
    }
  }


  /**
   * Lists all operatoreLogs entities.
   * @Route("/logs", name="admin_logs_index")
   * @Method({"GET", "POST"})
   */
  public function indexLogsAction(Request $request)
  {
    $table = $this->createDataTable()
      ->add('type', TextColumn::class, ['label' => 'Evento'])
      ->add('eventTime', DateTimeColumn::class, ['label' => 'Data', 'format' => 'd-m-Y H:i', 'searchable' => false])
      ->add('user', TextColumn::class, ['label' => 'Utente'])
      ->add('description', TextColumn::class, ['label' => 'Descrizione'])
      ->add('ip', TextColumn::class, ['label' => 'Ip'])
      ->createAdapter(ORMAdapter::class, [
        'entity' => AuditLog::class,
      ])
      ->handleRequest($request);

    if ($table->isCallback()) {
      return $table->getResponse();
    }

    return $this->render('@App/Admin/indexLogs.html.twig', [
      'user' => $this->getUser(),
      'datatable' => $table
    ]);
  }

  /**
   * Lists all scheduled actions entities.
   * @Route("/scheduled-actions", name="admin_scheduled_actions_index")
   * @Method({"GET", "POST"})
   */
  public function indexScheduledActionsAction(Request $request)
  {

    $table = $this->dataTableFactory->createFromType(ScheduledActionTableType::class)->handleRequest($request);

    if ($table->isCallback()) {
      return $table->getResponse();
    }

    return $this->render('@App/Admin/indexScheduledActions.html.twig', [
      'user' => $this->getUser(),
      'datatable' => $table
    ]);

  }


  /**
   * Lists all Services entities.
   * @Route("/servizio", name="admin_servizio_index")
   * @Method("GET")
   */
  public function indexServizioAction()
  {
    $statuses = [
      Servizio::STATUS_CANCELLED => $this->translator->trans('servizio.statutes.bozza'),
      Servizio::STATUS_AVAILABLE => $this->translator->trans('servizio.statutes.pubblicato'),
      Servizio::STATUS_SUSPENDED => $this->translator->trans('servizio.statutes.sospeso'),
      Servizio::STATUS_PRIVATE => $this->translator->trans('servizio.statutes.privato'),
      Servizio::STATUS_SCHEDULED => $this->translator->trans('servizio.statutes.schedulato'),
    ];

    $accessLevels = [
      Servizio::ACCESS_LEVEL_ANONYMOUS => 'Anonimo',
      Servizio::ACCESS_LEVEL_SOCIAL => 'Social',
      Servizio::ACCESS_LEVEL_SPID_L1 => 'Spid livello 1',
      Servizio::ACCESS_LEVEL_SPID_L2 => 'Spid livello 2',
      Servizio::ACCESS_LEVEL_CIE => 'Cie',
    ];

    $em = $this->getDoctrine()->getManager();
    $items = $em->getRepository('AppBundle:Servizio')->findBy([], ['name' => 'ASC']);

    return $this->render('@App/Admin/indexServizio.html.twig', [
      'user' => $this->getUser(),
      'items' => $items,
      'statuses' => $statuses,
      'access_levels' => $accessLevels,
    ]);
  }

  /**
   * Lists all operatoreUser entities.
   * @Route("/servizio/list", name="admin_servizio_list")
   * @Method("GET")
   */
  public function listServizioAction()
  {

    $em = $this->getDoctrine()->getManager();
    $items = $em->getRepository('AppBundle:Servizio')->findBy(['praticaFCQN' => '\AppBundle\Entity\FormIO'], ['name' => 'ASC']);

    $data = [];
    foreach ($items as $s) {
      $descLimit = 150;
      $description = strip_tags($s->getDescription());
      if (strlen($description) > $descLimit) {
        $description = utf8_encode(substr($description, 0, $descLimit) . '...');
      }
      $data [] = [
        'id' => $s->getId(),
        'title' => $s->getName(),
        'description' => $description
      ];
    }

    return new JsonResponse($data);
  }

  /**
   * @Route("/servizio/import", name="admin_servizio_import")
   * @param Request $request
   * @return array|RedirectResponse
   */
  public function importServizioAction(Request $request)
  {
    $em = $this->getDoctrine()->getManager();
    $ente = $this->instanceService->getCurrentInstance();

    $remoteUrl = $request->get('url');
    $client = new Client();
    $request = new \GuzzleHttp\Psr7\Request(
      'GET',
      $remoteUrl,
      ['Content-Type' => 'application/json']
    );

    try {
      $response = $client->send($request);

      if ($response->getStatusCode() == 200) {
        $responseBody = json_decode($response->getBody(), true);
        $responseBody['tenant'] = $ente->getId();

        $serviceDto = new Service();
        $form = $this->createForm('AppBundle\Form\ServizioFormType', $serviceDto);
        unset($responseBody['id'], $responseBody['slug']);

        $data = Service::normalizeData($responseBody);
        $form->submit($data, true);

        if ($form->isSubmitted() && !$form->isValid()) {
          $this->addFlash('error', 'Si è verificato un problema in fase di importazione.');
          return $this->redirectToRoute('admin_servizio_index');
        }

        $category = $em->getRepository('AppBundle:Categoria')->findOneBy(['slug' => $serviceDto->getTopics()]);
        if ($category instanceof Categoria) {
          $serviceDto->setTopics($category);
        }

        $service = $serviceDto->toEntity();
        $service->setName($service->getName() . ' (importato ' . date('d/m/Y H:i:s') . ')');
        $service->setPraticaFCQN('\AppBundle\Entity\FormIO');
        $service->setPraticaFlowServiceName('ocsdc.form.flow.formio');
        $service->setEnte($ente);
        // Erogatore
        $erogatore = new Erogatore();
        $erogatore->setName('Erogatore di ' . $service->getName() . ' per ' . $ente->getName());
        $erogatore->addEnte($ente);
        $em->persist($erogatore);
        $service->activateForErogatore($erogatore);

        // todo: verificare se è possibile eliminare
        $this->serviceManager->save($service);


        if (!empty($service->getFormIoId())) {
          $response = $this->formServer->cloneFormFromRemote($service, $remoteUrl . '/form');
          if ($response['status'] == 'success') {
            $formId = $response['form_id'];
            $flowStep = new FlowStep();
            $flowStep
              ->setIdentifier($formId)
              ->setType('formio')
              ->addParameter('formio_id', $formId);
            $service->setFlowSteps([$flowStep]);
            // Backup
            $additionalData = $service->getAdditionalData();
            $additionalData['formio_id'] = $formId;
            $service->setAdditionalData($additionalData);
          } else {
            $em->remove($service);
            $em->flush();
            $this->addFlash('error', 'Si è verificato un problema in fase creazione del form.');
            return $this->redirectToRoute('admin_servizio_index');
          }
        }

        $this->serviceManager->save($service);

        $this->addFlash('success', 'Servizio importato corettamente');
        return $this->redirectToRoute('admin_servizio_index');

      }
    } catch (\Exception $e) {
      $this->addFlash('error', $e->getMessage());
      $this->addFlash('error', 'Si è verificato un problema in fase creazione del form.');
    }
    return $this->redirectToRoute('admin_servizio_index');
  }

  /**
   * @Route("/servizio/{id}/edit", name="admin_servizio_edit")
   * @ParamConverter("id", class="AppBundle:Servizio")
   * @param Servizio $servizio
   * @param Request $request
   * @return Response
   */
  public function editServizioAction(Servizio $servizio, Request $request)
  {
    $user = $this->getUser();

    $steps = [
      'template' => [
        'label' => 'Template del form',
        'class' => FormIOTemplateType::class,
        'icon'  => 'fa-clone',
      ],
      'general' => [
        'label' => 'Dati generali',
        'class' => GeneralDataType::class,
        'icon'  => 'fa-file-o',
      ],
      'card' => [
        'label' => 'Scheda',
        'class' => CardDataType::class,
        'template' => 'AppBundle:Admin/servizio:_cardStep.html.twig',
        'icon'  => 'fa-file-text-o',
      ],
      'formio' => [
        'label' => 'Modulo',
        'class' => FormIOBuilderRenderType::class,
        'template' => 'AppBundle:Admin/servizio:_formIOBuilderStep.html.twig',
        'icon'  => 'fa-server',
      ],
      'formioI18n' => [
        'label' => 'Traduzioni modulo',
        'class' => FormIOI18nType::class,
        'template' => 'AppBundle:Admin/servizio:_formIOI18nStep.html.twig',
        'icon'  => 'fa-language',
      ],
      'messages' => [
        'label' => 'Messaggi',
        'class' => FeedbackMessagesDataType::class,
        'template' => 'AppBundle:Admin/servizio:_feedbackMessagesStep.html.twig',
        'icon'  => 'fa-envelope-o',
      ],
      'app-io' => [
        'label' => 'App IO',
        'class' => IOIntegrationDataType::class,
        'template' => 'AppBundle:Admin/servizio:_ioIntegrationStep.html.twig',
        'icon'  => 'fa-bullhorn',
      ],
      'payments' => [
        'label' => 'Dati pagamento',
        'class' => PaymentDataType::class,
        'template' => 'AppBundle:Admin/servizio:_paymentsStep.html.twig',
        'icon'  => 'fa-credit-card',
      ],
      'backoffices' => [
        'label' => 'Integrazioni',
        'class' => IntegrationsDataType::class,
        'template' => 'AppBundle:Admin/servizio:_backofficesStep.html.twig',
        'icon'  => 'fa-cogs',
      ],
      'protocol' => [
        'label' => 'Dati protocollo',
        'class' => ProtocolDataType::class,
        'icon'  => 'fa-folder-open-o',
      ]
    ];

    if ($servizio->isLegacy()) {
      unset($steps['template']);
      unset($steps['formio']);
    }

    if (!$servizio->isLegacy() && !empty($servizio->getFormIoId())) {
      unset($steps['template']);
    }

    if (count($this->locales) <= 1) {
      unset($steps['formioI18n']);
    }

    $currentStep = $request->query->get('step');
    $nexStep = false;
    $keys = array_keys($steps);
    if (!in_array($currentStep, $keys)) {
      $currentStep = $keys[0];
    }
    $currentKey = array_search($currentStep, $keys);
    if (isset($keys[$currentKey + 1])) {
      $nexStep = $keys[$currentKey + 1];
    }
    $schema = $this->schemaFactory->createFromFormId($servizio->getFormIoId(), false);
    $backofficeSchema = false;
    if ($servizio->getBackofficeFormId()) {
      $backofficeSchema = $this->schemaFactory->createFromFormId($servizio->getBackofficeFormId(), false);
    }

    $form = null;
    if (isset($steps[$currentStep]['class'])) {
      $form = $this->createForm($steps[$currentStep]['class'], $servizio);
      $form->handleRequest($request);
      if ($form->isSubmitted() && $form->isValid()) {
        $this->serviceManager->save($servizio);

        if ($request->request->get('save') === 'next') {
          return $this->redirectToRoute('admin_servizio_edit', ['id' => $servizio->getId(), 'step' => $nexStep]);
        }
        return $this->redirectToRoute('admin_servizio_edit', ['id' => $servizio->getId(), 'step' => $currentStep]);
      }
    }

    return $this->render('@App/Admin/editServizio.html.twig', [
      'form' => $form ? $form->createView() : null,
      'steps' => $steps,
      'current_step' => $currentStep,
      'next_step' => $nexStep,
      'servizio' => $servizio,
      'schema' => $schema,
      'backoffice_schema' => $backofficeSchema,
      'formserver_url' => $this->getParameter('formserver_admin_url'),
      'user' => $user
    ]);
  }

  /**
   * @Route("/servizio/{servizio}/custom-validation", name="admin_servizio_custom_validation")
   * @ParamConverter("servizio", class="AppBundle:Servizio")
   * @param Request $request
   * @param Servizio $servizio
   *
   * @return Response
   */
  public function editCustomValidationServizioAction(Request $request, Servizio $servizio)
  {
    $user = $this->getUser();

    $schema = $this->schemaFactory->createFromFormId($servizio->getFormIoId());

    $form = $this->createFormBuilder(null)->add(
      "post_submit_validation_expression", TextareaType::class, [
      "label" => 'Validazione al submit',
      'required' => false,
      'data' => $servizio->getPostSubmitValidationExpression()
    ])->add(
      "post_submit_validation_message", TextType::class, [
      "label" => 'Messaggio di errore in caso di mancata validazione',
      'required' => false,
      'data' => $servizio->getPostSubmitValidationMessage()
    ])->add(
      'Save', SubmitType::class
    )->getForm()->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $data = $form->getData();
      $servizio->setPostSubmitValidationExpression($data['post_submit_validation_expression']);
      $servizio->setPostSubmitValidationMessage($data['post_submit_validation_message']);
      $this->serviceManager->save($servizio);
      $this->addFlash('feedback', 'Validazione salvata correttamente');

      return $this->redirectToRoute('admin_servizio_custom_validation', ['servizio' => $servizio->getId()]);
    }

    return $this->render('@App/Admin/editCustomValidationServizio.html.twig', [
      'form' => $form->createView(),
      'servizio' => $servizio,
      'user' => $user,
      'schema' => $schema,
      'statuses' => Pratica::getStatuses()
    ]);
  }

  /**
   * Creates a new Service entity.
   * @Route("/servizio/new", name="admin_service_new")
   * @Method({"GET", "POST"})
   * @param Request $request
   * @return RedirectResponse|Response|null
   */
  public function newServiceAction(Request $request)
  {

    $servizio = new Servizio();
    $ente = $this->instanceService->getCurrentInstance();

    $servizio->setName('Nuovo Servizio ' . time());
    $servizio->setPraticaFCQN('\AppBundle\Entity\FormIO');
    $servizio->setPraticaFlowServiceName('ocsdc.form.flow.formio');
    $servizio->setEnte($ente);
    $servizio->setStatus(Servizio::STATUS_CANCELLED);
    $servizio->setProtocolRequired(false);

    // Erogatore
    $erogatore = new Erogatore();
    $erogatore->setName('Erogatore di ' . $servizio->getName() . ' per ' . $ente->getName());
    $erogatore->addEnte($ente);
    $this->getDoctrine()->getManager()->persist($erogatore);
    $servizio->activateForErogatore($erogatore);

    $this->serviceManager->save($servizio);

    return $this->redirectToRoute('admin_servizio_edit', ['id' => $servizio->getId()]);

  }

  /**
   * Deletes a service entity.
   * @Route("/servizio/{id}/delete", name="admin_servizio_delete")
   * @Method("GET")
   */
  public function deleteServiceAction(Request $request, Servizio $servizio)
  {

    try {
      if ($servizio->getPraticaFCQN() == '\AppBundle\Entity\FormIO') {
        $this->formServer->deleteForm($servizio);
      }

      $em = $this->getDoctrine()->getManager();
      $em->remove($servizio);
      $em->flush();

      $this->addFlash('feedback', 'Servizio eliminato correttamente');

      return $this->redirectToRoute('admin_servizio_index');
    } catch (ForeignKeyConstraintViolationException $exception) {
      $this->addFlash('warning', 'Impossibile eliminare il servizio, ci sono delle pratiche collegate.');
      return $this->redirectToRoute('admin_servizio_index');
    }

  }

  /**
   * @Route("/servizio/{servizio}/schema", name="admin_servizio_schema_edit")
   * @ParamConverter("servizio", class="AppBundle:Servizio")
   * @param Request $request
   * @param Servizio $servizio
   * @return JsonResponse
   */
  public function formioValidateAction(Request $request, Servizio $servizio)
  {

    $data = $request->get('schema');
    if (!empty($data)) {
      $schema = \json_decode($data, true);

      try {
        $response = $this->formServer->editForm($schema);
        return JsonResponse::create($response, Response::HTTP_OK);
      } catch (\Exception $exception) {
        return JsonResponse::create($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
      }
    }
  }

  /**
   * @Route("/io-test", name="test_io")
   * @Method({"POST"})
   * @param Request $request
   *
   * @return array|JsonResponse
   */
  public function testIo(Request $request)
  {
    $serviceId = $request->get('service_id');
    $primaryKey = $request->get('primary_key');
    $secondaryKey = $request->get('secondary_key');
    $fiscalCode = $request->get('fiscal_code');

    if (!($serviceId && $primaryKey && $fiscalCode)) {
      return new JsonResponse(
        ["error" => $this->translator->trans('app_io.errore.parametro_mancante')],
        Response::HTTP_BAD_REQUEST);
    }

    $response = $this->ioService->test($serviceId, $primaryKey, $secondaryKey, $fiscalCode);
    if (key_exists('error', $response)) {
      return new JsonResponse($response, Response::HTTP_BAD_REQUEST);
    } else {
      return new JsonResponse($response, Response::HTTP_OK);
    }
  }


  /**
   * @Route("/usage",name="admin_usage")
   * @return Response
   */
  public function usageAction()
  {

    $sql = "SELECT distinct(extract(year from u.created_at)) as year, count(distinct u.id) as count
            FROM utente AS u WHERE u.type = 'cps' and u.created_at IS NOT NULL GROUP BY 1 ORDER BY year ";

    /** @var EntityManager $em */
    $em = $this->getDoctrine()->getManager();
    try {
      $stmt = $em->getConnection()->prepare($sql);
      $stmt->execute();
      $result = $stmt->fetchAll(FetchMode::ASSOCIATIVE);
    } catch (DBALException $e) {
      $this->logger->error($e->getMessage());
      $result = [];
    }

    return $this->render('@App/Admin/usage.html.twig', [
      'all_user' => $result,
      'user' => $this->getUser(),
    ]);
  }

  /**
   * @Route("/usage/metriche", name="admin_metriche")
   * @Method("GET")
   * @param Request $request
   * @return Response
   */
  public function metricheAction(Request $request)
  {
    $status = $request->get('status');
    $services = $request->get('services');
    $year = (int)$request->get('time');

    //Minutes for 6 Months
    $time = 2592000;
    $timeDiff = "- " . ($time / 60 / 24) . " days";

    $timeZone = date_default_timezone_get();

    $calculateInterval = date('Y-m-d H:i:s', strtotime($timeDiff));

    //TODO create filter bu type authentication
    /* $where = '';
     if ($services && $services != 'all') {
       $where .= " AND p.authentication_data = ?";
     }*/
    $isUserActive = "";
    if ($status && $status != 'all') {
      $isUserActive .= " LEFT JOIN pratica AS p ON p.user_id = u.id where TO_TIMESTAMP(p.creation_time) AT TIME ZONE '" . $timeZone . "' >= '" . $calculateInterval . "'" . "and p.creation_time IS NOT NULL";
    }

    $slqGrouped = "u.type = 'cps' and u.created_at IS NOT NULL GROUP BY 1,2";
    $where = $isUserActive != '' ? ' and ' : ' where ';

    $sql = "SELECT to_char(u.created_at,'month') AS month, extract(year from u.created_at) as year, count(DISTINCT u.id) as count
            FROM utente AS u " .
      $isUserActive .
      $where . $slqGrouped;

    /** @var EntityManager $em */
    $em = $this->getDoctrine()->getManager();
    try {

      $stmt = $em->getConnection()->executeQuery($sql);
      $result = $stmt->fetchAll(FetchMode::ASSOCIATIVE);
    } catch (DBALException $e) {
      $this->logger->error($e->getMessage());
      $result = [];
    }

    $series = array(
      array(
        'name' => 'Utenti registrati',
        'data' => array_fill(0, 12, 0)
      ),
      array(
        'name' => 'Utenti attivi negli ultimi 6 mesi',
        'data' => array_fill(0, 12, 0)
      )
    );

    $resultByYear = array_filter($result, function ($obj) use ($year) {
      if (count($obj) > 0) {
        if ($obj['year'] == $year) return true;
      }
      return false;
    });

    $listMonths = ["january", "february", "march", "april", "may", "june", "july", "august", "september", "october", "november", "december"];

    foreach ($listMonths as $key => $value) {
      foreach ($resultByYear as $r) {
        if (trim($r['month']) == $value) {
          $series[0]['name'] = 'Utenti registrasti';
          $series[0]['data'][$key] = [$r['count']];
        }
        if ($status && $status != 'all') {
          if (trim($r['month']) == $value) {
            $series[1]['name'] = 'Utenti attivi negli ultimi 6 mesi';
            $series[1]['data'][$key] = [$r['count']];
          }
        }
      }
    }

    $data['series'] = $series;
    return new Response(json_encode($data), 200);

  }

}



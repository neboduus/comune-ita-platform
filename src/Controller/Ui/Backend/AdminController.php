<?php

namespace App\Controller\Ui\Backend;

use App\DataTable\ScheduledActionTableType;
use App\DataTable\Traits\FiltersTrait;
use App\Dto\ServiceDto;
use App\Entity\AuditLog;
use App\Entity\Categoria;
use App\Entity\Erogatore;
use App\Entity\OperatoreUser;
use App\Entity\Pratica;
use App\Entity\ScheduledAction;
use App\Entity\Servizio;
use App\Entity\Webhook;
use App\Form\Admin\Servizio\CardDataType;
use App\Form\Admin\Servizio\FeedbackMessagesDataType;
use App\Form\Admin\Servizio\FormIOBuilderRenderType;
use App\Form\Admin\Servizio\FormIOI18nType;
use App\Form\Admin\Servizio\FormIOTemplateType;
use App\Form\Admin\Servizio\GeneralDataType;
use App\Form\Admin\Servizio\IntegrationsDataType;
use App\Form\Admin\Servizio\IOIntegrationDataType;
use App\Form\Admin\Servizio\PaymentDataType;
use App\Form\Admin\Servizio\ProtocolDataType;
use App\FormIO\SchemaFactoryInterface;
use App\Model\FlowStep;
use App\Model\PublicFile;
use App\Model\Service;
use App\Model\ServiceSource;
use App\Services\FileService\ServiceAttachmentsFileService;
use App\Services\FormServerApiAdapterService;
use App\Services\InstanceService;
use App\Services\IOService;
use App\Services\MailerService;
use App\Services\Manager\ServiceManager;
use App\Services\Manager\UserManager;
use App\Services\Satisfy\SatisfyService;
use App\Utils\FormUtils;
use App\Utils\StringUtils;
use DateTime;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use League\Flysystem\FileNotFoundException;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


/**
 * Class AdminController
 * @Route("/admin")
 */
class AdminController extends AbstractController
{
  use FiltersTrait;

  /** @var InstanceService */
  private $instanceService;

  /** @var FormServerApiAdapterService */
  private $formServer;

  /** @var TranslatorInterface */
  private $translator;

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
   * @var MailerService
   */
  private $mailer;
  /**
   * @var EntityManagerInterface
   */
  private $entityManager;

  /**
   * @var UserManager $userManager
   */
  private $userManager;

  /**
   * @var ServiceAttachmentsFileService
   */
  private $fileService;
  private $defaultLocale;

  /**
   * @param InstanceService $instanceService
   * @param FormServerApiAdapterService $formServer
   * @param TranslatorInterface $translator
   * @param SchemaFactoryInterface $schemaFactory
   * @param IOService $ioService
   * @param RouterInterface $router
   * @param DataTableFactory $dataTableFactory
   * @param LoggerInterface $logger
   * @param ServiceManager $serviceManager
   * @param UserManager $userManager
   * @param MailerService $mailer
   * @param ServiceAttachmentsFileService $fileService
   * @param EntityManagerInterface $entityManager
   * @param $locales
   */
  public function __construct(
    InstanceService $instanceService,
    FormServerApiAdapterService $formServer,
    TranslatorInterface $translator,
    SchemaFactoryInterface $schemaFactory,
    IOService $ioService,
    RouterInterface $router,
    DataTableFactory $dataTableFactory,
    LoggerInterface $logger,
    ServiceManager $serviceManager,
    UserManager $userManager,
    MailerService $mailer,
    EntityManagerInterface $entityManager,
    ServiceAttachmentsFileService $fileService,
    $locales
  ) {
    $this->instanceService = $instanceService;
    $this->formServer = $formServer;
    $this->translator = $translator;
    $this->schemaFactory = $schemaFactory;
    $this->ioService = $ioService;
    $this->router = $router;
    $this->dataTableFactory = $dataTableFactory;
    $this->logger = $logger;
    $this->serviceManager = $serviceManager;
    $this->userManager = $userManager;
    $this->locales = explode('|', $locales);
    $this->mailer = $mailer;
    $this->entityManager = $entityManager;
    $this->fileService = $fileService;
  }


  /**
   * @Route("/", name="admin_index")
   * @param Request $request
   * @return Response
   */
  public function indexAction(Request $request)
  {
    return $this->render('Admin/index.html.twig', [
      'user' => $this->getUser(),
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
    $services ['all'] = $this->translator->trans('tutti');
    foreach ($servizi as $s) {
      $services[$s->getId()] = $s->getName();
    }

    $entityManager = $this->getDoctrine()->getManager();
    $ente = $this->instanceService->getCurrentInstance();

    $form = $this->createForm('App\Form\Admin\Ente\EnteType', $ente);
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
      $ente = $form->getData();

      $entityManager->persist($ente);
      $entityManager->flush();

      return $this->redirectToRoute('admin_edit_ente');
    }

    return $this->render('Admin/editEnte.html.twig', [
      'user' => $this->getUser(),
      'ente' => $ente,
      'statuses' => Webhook::TRIGGERS,
      'services' => $services,
      'form' => $form->createView(),
      'locales' => $this->locales
    ]);
  }


  /**
   * Lists all operatoreUser entities.
   * @Route("/operatore", name="admin_operatore_index")
   * @Method("GET")
   */
  public function indexOperatoreAction(): Response
  {
    $em = $this->getDoctrine()->getManager();

    $operatoreUsers = $em->getRepository('App\Entity\OperatoreUser')->findAll();

    return $this->render('Admin/indexOperatore.html.twig', [
      'user' => $this->getUser(),
      'operatoreUsers' => $operatoreUsers,
    ]);
  }

  /**
   * Creates a new operatoreUser entity.
   * @Route("/operatore/new", name="admin_operatore_new")
   * @Method({"GET", "POST"})
   * @param Request $request
   * @param UserPasswordEncoderInterface $passwordEncoder
   * @return Response
   */
  public function newOperatoreAction(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
  {
    $operatoreUser = new Operatoreuser();
    $form = $this->createForm('App\Form\OperatoreUserType', $operatoreUser);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $em = $this->getDoctrine()->getManager();
      $ente = $this->instanceService->getCurrentInstance();

      $operatoreUser
        ->setEnte($ente)
        ->setPlainPassword(md5(time()))
        ->setEnabled(true);

      $operatoreUser->setPassword(
        $passwordEncoder->encodePassword(
          $operatoreUser,
          StringUtils::randomPassword()
        )
      );

      $this->userManager->resetPassword($operatoreUser);
      $this->userManager->save($operatoreUser);

      $this->addFlash('feedback', $this->translator->trans('admin.create_operator_notify'));

      return $this->redirectToRoute('admin_operatore_show', array('id' => $operatoreUser->getId()));
    }

    return $this->render('Admin/editOperatore.html.twig', [
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
  public function showOperatoreAction(OperatoreUser $operatoreUser): Response
  {
    if ($operatoreUser->getServiziAbilitati()->count() > 0) {
      $serviziAbilitati = $this->getDoctrine()
        ->getRepository(Servizio::class)
        ->findBy(['id' => $operatoreUser->getServiziAbilitati()->toArray()]);
    } else {
      $serviziAbilitati = [];
    }

    return $this->render('Admin/showOperatore.html.twig', [
      'user' => $this->getUser(),
      'operatoreUser' => $operatoreUser,
      'servizi_abilitati' => $serviziAbilitati,
    ]);
  }

  /**
   * Displays a form to edit an existing operatoreUser entity.
   * @Route("/operatore/{id}/edit", name="admin_operatore_edit")
   * @Method({"GET", "POST"})
   */
  public function editOperatoreAction(Request $request, OperatoreUser $operatoreUser)
  {
    $form = $this->createForm('App\Form\OperatoreUserType', $operatoreUser);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $this->getDoctrine()->getManager()->flush();

      return $this->redirectToRoute('admin_operatore_edit', array('id' => $operatoreUser->getId()));
    }

    return $this->render('Admin/editOperatore.html.twig', [
      'user' => $this->getUser(),
      'operatoreUser' => $operatoreUser,
      'form' => $form->createView(),
    ]);
  }

  /**
   * Send password reset hash to user.
   * @Route("/operatore/{id}/resetpassword", name="admin_operatore_reset_password")
   * @Method({"GET", "POST"})
   */
  public function resetPasswordOperatoreAction(Request $request, OperatoreUser $operatoreUser): RedirectResponse
  {
    $em = $this->getDoctrine()->getManager();
    $this->userManager->resetPassword($operatoreUser);
    $em->persist($operatoreUser);
    $em->flush();

    return $this->redirectToRoute('admin_operatore_edit', array('id' => $operatoreUser->getId()));
  }

  /**
   * Deletes a operatoreUser entity.
   * @Route("/operatore/{id}/delete", name="admin_operatore_delete")
   * @Method({"GET", "POST", "DELETE"})
   */
  public function deleteOperatoreAction(Request $request, OperatoreUser $operatoreUser): RedirectResponse
  {
    try {
      $this->userManager->remove($operatoreUser);
      $this->addFlash('feedback', $this->translator->trans('admin.delete_operator_notify'));

      return $this->redirectToRoute('admin_operatore_index');

    } catch (ForeignKeyConstraintViolationException $exception) {
      $this->addFlash('warning', $this->translator->trans('admin.error_delete_operator_notify'));

      return $this->redirectToRoute('admin_servizio_index');
    }
  }


  /**
   * Lists all operatoreLogs entities.
   * @Route("/logs", name="admin_logs_index")
   * @Method({"GET", "POST"})
   */
  public function indexLogsAction(Request $request): Response
  {
    $table = $this->dataTableFactory->create()
      ->add('type', TextColumn::class, ['label' => $this->translator->trans('event')])
      ->add(
        'eventTime',
        DateTimeColumn::class,
        ['label' => $this->translator->trans('date'), 'format' => 'd-m-Y H:i', 'searchable' => false]
      )
      ->add('user', TextColumn::class, ['label' => $this->translator->trans('meetings.labels.user')])
      ->add('description', TextColumn::class, ['label' => $this->translator->trans('general.descrizione')])
      ->add('ip', TextColumn::class, ['label' => 'Ip'])
      ->createAdapter(ORMAdapter::class, [
        'entity' => AuditLog::class,
      ])
      ->handleRequest($request);

    if ($table->isCallback()) {
      return $table->getResponse();
    }

    return $this->render('Admin/indexLogs.html.twig', [
      'user' => $this->getUser(),
      'datatable' => $table,
    ]);
  }

  /**
   * Lists all scheduled actions entities.
   * @Route("/scheduled-actions", name="admin_scheduled_actions_index", methods={"GET", "POST"})
   * @throws \Doctrine\DBAL\Driver\Exception
   */
  public function indexScheduledActionsAction(Request $request): Response
  {

    $statuses = [
      ScheduledAction::STATUS_PENDING => [
        'label' => $this->translator->trans('STATUS_WAITING'),
        'count' => 0,
      ],
      ScheduledAction::STATUS_DONE => [
        'label' => $this->translator->trans('STATUS_DONE'),
        'count' => 0,
      ],
      ScheduledAction::STATUS_INVALID => [
        'label' => $this->translator->trans('STATUS_INVALID'),
        'count' => 0,
      ],
    ];

    $sql = "SELECT count(id) as count, status FROM scheduled_action GROUP BY status";
    try {
      $stmt = $this->entityManager->getConnection()->prepare($sql);
      $stmt->executeQuery();
      $result = $stmt->fetchAllAssociative();
      foreach ($result as $r) {
        $statuses[$r['status']]['count'] = $r['count'];
      }

    } catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }

    $options = self::getFiltersFromRequest($request);
    $table = $this->dataTableFactory->createFromType(ScheduledActionTableType::class, $options)
      ->handleRequest($request);

    if ($table->isCallback()) {
      return $table->getResponse();
    }

    return $this->render('Admin/indexScheduledActions.html.twig', [
      'user' => $this->getUser(),
      'datatable' => $table,
      'filters' => $options['filters'] ?? [],
      'statuses' => $statuses,
    ]);
  }

  /**
   * Reset retry of a scheduled action
   * @Route("/scheduled-actions/{id}/retry", name="admin_scheduled_actions_retry")
   * @Method({"GET"})
   */
  public function retryScheduledActionsAction(Request $request, ScheduledAction $scheduledAction): JsonResponse
  {
    try {
      $scheduledAction->setRetry(0);
      $scheduledAction->setHostname(null);
      $this->entityManager->persist($scheduledAction);
      $this->entityManager->flush();

      return new JsonResponse(['status' => 'success']);
    } catch (\Exception $e) {
      return new JsonResponse([
          'status' => 'error',
          'message' => $e->getMessage(),
        ]
      );
    }

  }


  /**
   * Lists all Services entities.
   * @Route("/servizio", name="admin_servizio_index")
   * @Method("GET")
   */
  public function indexServizioAction(): Response
  {
    $statuses = [
      Servizio::STATUS_CANCELLED => $this->translator->trans('servizio.statutes.bozza'),
      Servizio::STATUS_AVAILABLE => $this->translator->trans('servizio.statutes.pubblicato'),
      Servizio::STATUS_SUSPENDED => $this->translator->trans('servizio.statutes.sospeso'),
      Servizio::STATUS_PRIVATE => $this->translator->trans('servizio.statutes.privato'),
      Servizio::STATUS_SCHEDULED => $this->translator->trans('servizio.statutes.schedulato'),
    ];

    $accessLevels = [
      Servizio::ACCESS_LEVEL_ANONYMOUS => $this->translator->trans('general.anonymous'),
      Servizio::ACCESS_LEVEL_SOCIAL => $this->translator->trans('general.social'),
      Servizio::ACCESS_LEVEL_SPID_L1 => $this->translator->trans('general.level_spid_1'),
      Servizio::ACCESS_LEVEL_SPID_L2 => $this->translator->trans('general.level_spid_2'),
      Servizio::ACCESS_LEVEL_CIE => $this->translator->trans('general.cie'),
    ];

    $em = $this->getDoctrine()->getManager();
    $items = $em->getRepository('App\Entity\Servizio')->findBy([], ['name' => 'ASC']);

    return $this->render('Admin/indexServizio.html.twig', [
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
  public function listServizioAction(): JsonResponse
  {

    $em = $this->getDoctrine()->getManager();
    $items = $em->getRepository('App\Entity\Servizio')->findBy(['praticaFCQN' => '\App\Entity\FormIO'],
      ['name' => 'ASC']);

    $data = [];
    foreach ($items as $s) {
      $descLimit = 150;
      $description = strip_tags($s->getDescription());
      if (strlen($description) > $descLimit) {
        $description = utf8_encode(substr($description, 0, $descLimit).'...');
      }
      $data [] = [
        'id' => $s->getId(),
        'title' => $s->getName(),
        'description' => $description,
      ];
    }

    return new JsonResponse($data);
  }

  /**
   * @Route("/servizio/import", name="admin_servizio_import")
   * @param Request $request
   * @param ServiceDto $serviceDto
   * @return RedirectResponse
   */
  public function importServizioAction(Request $request, ServiceDto $serviceDto): RedirectResponse
  {
    $em = $this->getDoctrine()->getManager();
    $ente = $this->instanceService->getCurrentInstance();

    $remoteUrl = $request->get('url');
    $client = new Client();
    $serviceRequest = new \GuzzleHttp\Psr7\Request(
      'GET',
      $remoteUrl,
      [
        'Content-Type' => 'application/json',
        'x-locale' => $request->getLocale(),
      ]
    );

    try {
      $response = $client->send($serviceRequest);

      if ($response->getStatusCode() == 200) {
        $responseBody = json_decode($response->getBody(), true);
        $responseBody['tenant'] = $ente->getId();

        $dto = new Service();
        $form = $this->createForm('App\Form\ServizioFormType', $dto);
        $serviceId = $responseBody['id'];
        $md5Response = md5(json_encode($responseBody));
        $identifier = $responseBody['identifier'] ?? null;
        unset($responseBody['id'], $responseBody['slug']);
        $data = ServiceDto::normalizeData($responseBody);

        // Populates default messages in the language provided in the request if not provided or incomplete
        // Messages may not be valued because they were entered only when the service was first saved
        $defaultFeedbackMessages = $this->serviceManager->getDefaultFeedbackMessages()[$request->getLocale()];

        // Todo: sistemare assolutamente
        foreach ($data['feedback_messages'] as $statusName => $feedbackMessage) {
          $status = Pratica::getStatusCodeByName($statusName);
          if (!isset($feedbackMessage['subject']) || !$feedbackMessage['subject']) {
            $feedbackMessage['subject'] = $defaultFeedbackMessages[$status]->getSubject();
          }
          if (!isset($feedbackMessage['message']) || !$feedbackMessage['message']) {
            $feedbackMessage['message'] = $defaultFeedbackMessages[$status]->getMessage();
          }
          if (!isset($feedbackMessage['is_active'])) {
            $feedbackMessage['is_active'] = $defaultFeedbackMessages[$status]->isActive();
          }
          $data['feedback_messages'][$statusName] = $feedbackMessage;
        }

        $form->submit($data, true);

        if ($form->isSubmitted() && !$form->isValid()) {
          $errors = FormUtils::getErrorsFromForm($form);
          $this->addFlash('error', $this->translator->trans('servizio.error_import'));
          foreach ($errors as $e ) {
            if (is_array($e)) {
              $this->addFlash('error', implode(', ', $e));
            } else {
              $this->addFlash('error', $e);
            }
          }
          $this->logger->error("Import validation error: ", $errors);
          return $this->redirectToRoute('admin_servizio_index');
        }

        try {
          $updatedAt = $responseBody['updated_at'] ? new DateTime($data['updated_at']) : null;
        } catch (\Exception $e) {
          $updatedAt = null;
        }
        $serviceSource = new ServiceSource();
        $serviceSource
          ->setId($serviceId)
          ->setUrl($remoteUrl)
          ->setUpdatedAt($updatedAt)
          ->setMd5($md5Response)
          ->setVersion('1')
          ->setIdentifier($identifier);
        $dto = $dto->setSource($serviceSource);

        $this->serviceManager->checkServiceRelations($dto);
        $service = $serviceDto->toEntity($dto);

        $service->setIdentifier($serviceSource->getIdentifier());
        $importedAt = ' ('.$this->translator->trans('imported').' '.date('d/m/Y H:i:s').')';
        $shortenedImportedServiceName = StringUtils::shortenString($service->getName(), 255 - strlen($importedAt));
        $service->setName($shortenedImportedServiceName.$importedAt);
        $service->setPraticaFCQN('\App\Entity\FormIO');
        $service->setPraticaFlowServiceName('ocsdc.form.flow.formio');
        $service->setEnte($ente);
        // Erogatore
        $erogatore = new Erogatore();
        $erogatoreName = $this->translator->trans('provider_of').' '.$service->getName().' '.$this->translator->trans(
            'for'
          ).' '.$ente->getName();
        $limit = strlen($erogatoreName) < 255 ? 255 : 255 - (strlen(
              $erogatoreName
            ) - 255); // nuova lunghezza limite in base alla lunghezza in eccesso dell'erogatore
        $shortenedImportedServiceName = StringUtils::shortenString(
            $shortenedImportedServiceName,
            $limit - strlen($importedAt)
          ).$importedAt;
        $erogatore->setName(
          $this->translator->trans('provider_of').' '.$shortenedImportedServiceName.' '.$this->translator->trans(
            'for'
          ).' '.$ente->getName()
        );
        $erogatore->addEnte($ente);
        $em->persist($erogatore);
        $service->activateForErogatore($erogatore);

        // todo: verificare se è possibile eliminare
        $this->serviceManager->save($service);

        if (!empty($service->getFormIoId())) {
          $response = $this->formServer->cloneFormFromRemote($service, $remoteUrl.'/form');
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
            $this->addFlash('error', $this->translator->trans('servizio.error_create_form'));

            return $this->redirectToRoute('admin_servizio_index');
          }
        }

        $this->serviceManager->save($service);

        $this->addFlash('success', $this->translator->trans('servizio.success_import_service'));

        return $this->redirectToRoute('admin_servizio_index');

      }
    } catch (UniqueConstraintViolationException $e) {
      $this->logger->error("Import error: duplicated identifier {$identifier}");
      $this->addFlash(
        'error',
        $this->translator->trans(
          'servizio.error_duplicated_identifier',
          ['%identifier%' => $identifier]
        )
      );
    } catch (\Exception $e) {
      $this->logger->error("Import error: ".$e->getMessage());
      $this->addFlash('error', $this->translator->trans('servizio.error_create_form'));
    }

    return $this->redirectToRoute('admin_servizio_index');
  }

  /**
   * @Route("/servizio/{id}/edit", name="admin_servizio_edit")
   * @ParamConverter("id", class="App\Entity\Servizio")
   * @param Servizio $servizio
   * @param Request $request
   * @return Response
   */
  public function editServizioAction(Servizio $servizio, Request $request, SatisfyService $satisfyService): Response
  {
    $user = $this->getUser();
    $steps = [
      'template' => [
        'label' => $this->translator->trans('general.form_template'),
        'class' => FormIOTemplateType::class,
        'icon' => 'fa-clone',
      ],
      'general' => [
        'label' => $this->translator->trans('operatori.dati_generali'),
        'class' => GeneralDataType::class,
        'template' => 'Admin/servizio/_generalStep.html.twig',
        'icon' => 'fa-file-o',
      ],
      'card' => [
        'label' => $this->translator->trans('operatori.scheda'),
        'class' => CardDataType::class,
        'template' => 'Admin/servizio/_cardStep.html.twig',
        'icon' => 'fa-file-text-o',
      ],
      'formio' => [
        'label' => $this->translator->trans('operatori.modulo'),
        'class' => FormIOBuilderRenderType::class,
        'template' => 'Admin/servizio/_formIOBuilderStep.html.twig',
        'icon' => 'fa-server',
      ],
      'formioI18n' => [
        'label' => $this->translator->trans('servizio.i18n.translations_module'),
        'class' => FormIOI18nType::class,
        'template' => 'Admin/servizio/_formIOI18nStep.html.twig',
        'icon' => 'fa-language',
      ],
      'messages' => [
        'label' => $this->translator->trans('operatori.messaggi.titolo'),
        'class' => FeedbackMessagesDataType::class,
        'template' => 'Admin/servizio/_feedbackMessagesStep.html.twig',
        'icon' => 'fa-envelope-o',
      ],
      'app-io' => [
        'label' => $this->translator->trans('app_io.title'),
        'class' => IOIntegrationDataType::class,
        'template' => 'Admin/servizio/_ioIntegrationStep.html.twig',
        'icon' => 'fa-bullhorn',
      ],
      'payments' => [
        'label' => $this->translator->trans('general.payment_data'),
        'class' => PaymentDataType::class,
        'template' => 'Admin/servizio/_paymentsStep.html.twig',
        'icon' => 'fa-credit-card',
      ],
      'backoffices' => [
        'label' => $this->translator->trans('integrations'),
        'class' => IntegrationsDataType::class,
        'template' => 'Admin/servizio/_backofficesStep.html.twig',
        'icon' => 'fa-cogs',
      ],
      'protocol' => [
        'label' => $this->translator->trans('general.protocol_data'),
        'class' => ProtocolDataType::class,
        'icon' => 'fa-folder-open-o',
      ],
    ];

    if ($servizio->isLegacy() || $servizio->isBuiltIn()) {
      unset($steps['template']);
      unset($steps['formio']);
      unset($steps['formioI18n']);
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

        if (empty($servizio->getSatisfyEntrypointId())) {
          try {
            $satisfyService->syncEntryPoint($servizio, false);
          } catch (\Exception $e) {
            $this->logger->error('Error on configure satisfy entrypoint for service '.$servizio->getName().' - '.$e->getMessage());
          }
        }

        $this->serviceManager->save($servizio);

        if ($request->request->get('save') === 'next') {
          return $this->redirectToRoute('admin_servizio_edit', ['id' => $servizio->getId(), 'step' => $nexStep]);
        }

        return $this->redirectToRoute('admin_servizio_edit', ['id' => $servizio->getId(), 'step' => $currentStep]);
      }
    }

    $missingRequiredFields = $this->serviceManager->getMissingCardFields($servizio);
    if ($missingRequiredFields) {
      $this->addFlash('warning', $this->translator->trans('servizio.campi_pnrr_mancanti',  [
          '%nome_campi%' => $missingRequiredFields
        ])
      );
    }

    return $this->render('Admin/editServizio.html.twig', [
      'form' => $form ? $form->createView() : null,
      'steps' => $steps,
      'current_step' => $currentStep,
      'next_step' => $nexStep,
      'servizio' => $servizio,
      'schema' => $schema,
      'backoffice_schema' => $backofficeSchema,
      'formserver_url' => $this->getParameter('formserver_admin_url'),
      'user' => $user,
    ]);
  }

  /**
   * @Route("/servizio/{servizio}/custom-validation", name="admin_servizio_custom_validation")
   * @ParamConverter("servizio", class="App\Entity\Servizio")
   * @param Request $request
   * @param Servizio $servizio
   *
   * @return Response
   */
  public function editCustomValidationServizioAction(Request $request, Servizio $servizio): Response
  {
    $user = $this->getUser();

    $schema = $this->schemaFactory->createFromFormId($servizio->getFormIoId());

    $form = $this->createFormBuilder(null)->add(
      "post_submit_validation_expression",
      TextareaType::class,
      [
        "label" => $this->translator->trans('servizio.validate_submit'),
        'required' => false,
        'data' => $servizio->getPostSubmitValidationExpression(),
      ]
    )->add(
      "post_submit_validation_message",
      TextType::class,
      [
        "label" => $this->translator->trans('servizio.validate_error_service'),
        'required' => false,
        'data' => $servizio->getPostSubmitValidationMessage(),
      ]
    )->add(
      'Save',
      SubmitType::class
    )->getForm()->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $data = $form->getData();
      $servizio->setPostSubmitValidationExpression($data['post_submit_validation_expression']);
      $servizio->setPostSubmitValidationMessage($data['post_submit_validation_message']);
      $this->serviceManager->save($servizio);
      $this->addFlash('feedback', $this->translator->trans('servizio.validate_service'));

      return $this->redirectToRoute('admin_servizio_custom_validation', ['servizio' => $servizio->getId()]);
    }

    return $this->render('Admin/editCustomValidationServizio.html.twig', [
      'form' => $form->createView(),
      'servizio' => $servizio,
      'user' => $user,
      'schema' => $schema,
      'statuses' => Pratica::getStatuses(),
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

    $name = 'Nuovo Servizio '.time();
    $servizio->setName($name);
    $servizio->setShortDescription($name);
    $servizio->setPraticaFCQN('\App\Entity\FormIO');
    $servizio->setPraticaFlowServiceName('ocsdc.form.flow.formio');
    $servizio->setEnte($ente);
    $servizio->setStatus(Servizio::STATUS_CANCELLED);
    $servizio->setProtocolRequired(false);
    $category = $this->entityManager->getRepository(Categoria::class)->findOneBy([], ['name' => 'ASC']);
    if ($category instanceof Categoria) {
      $servizio->setTopics($category);
    }

    $defaultFeedbackMessages = $this->serviceManager->getDefaultFeedbackMessages();
    $translationsRepo = $this->entityManager->getRepository('Gedmo\Translatable\Entity\Translation');

    foreach ($this->locales as $locale) {
      $translationsRepo->translate($servizio, "feedbackMessages", $locale, $defaultFeedbackMessages[$locale]);
    }

    // Erogatore
    $erogatore = new Erogatore();
    $erogatore->setName(
      $this->translator->trans('provider_of').' '.$servizio->getName().' '.$this->translator->trans(
        'for'
      ).' '.$ente->getName()
    );
    $erogatore->addEnte($ente);
    $this->entityManager->persist($erogatore);
    $servizio->activateForErogatore($erogatore);
    $this->serviceManager->save($servizio);

    return $this->redirectToRoute('admin_servizio_edit', ['id' => $servizio->getId()]);

  }

  /**
   * Deletes a service entity.
   * @Route("/servizio/{id}/delete", name="admin_servizio_delete")
   * @Method("GET")
   */
  public function deleteServiceAction(Request $request, Servizio $servizio): RedirectResponse
  {

    try {
      if ($servizio->getPraticaFCQN() == '\App\Entity\FormIO') {
        $this->formServer->deleteForm($servizio);
      }

      $em = $this->getDoctrine()->getManager();
      $em->remove($servizio);
      $em->flush();

      $this->addFlash('feedback', $this->translator->trans('servizio.service_successfully_deleted'));

      return $this->redirectToRoute('admin_servizio_index');
    } catch (ForeignKeyConstraintViolationException $exception) {
      $this->addFlash('warning', $this->translator->trans('servizio.impossible_delete_service'));

      return $this->redirectToRoute('admin_servizio_index');
    }

  }

  /**
   * @Route("/servizio/{servizio}/schema", name="admin_servizio_schema_edit")
   * @ParamConverter("servizio", class="App\Entity\Servizio")
   * @param Request $request
   * @param Servizio $servizio
   * @return JsonResponse
   */
  public function formioValidateAction(Request $request, Servizio $servizio): JsonResponse
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
   * @return JsonResponse
   */
  public function testIo(Request $request): JsonResponse
  {
    $serviceId = $request->get('service_id');
    $primaryKey = $request->get('primary_key');
    $secondaryKey = $request->get('secondary_key');
    $fiscalCode = $request->get('fiscal_code');

    if (!($serviceId && $primaryKey && $fiscalCode)) {
      return new JsonResponse(
        ["error" => $this->translator->trans('app_io.errore.parametro_mancante')],
        Response::HTTP_BAD_REQUEST
      );
    }

    $response = $this->ioService->test($serviceId, $primaryKey, $secondaryKey, $fiscalCode);
    if (key_exists('error', $response)) {
      return new JsonResponse($response, Response::HTTP_BAD_REQUEST);
    } else {
      return new JsonResponse($response, Response::HTTP_OK);
    }
  }

  /**
   * @Route("/servizio/{servizio}/attachments/{attachmentType}/{filename}", name="admin_delete_service_attachment", methods={"DELETE"})
   * @ParamConverter("servizio", class="App\Entity\Servizio")
   * @param Request $request
   * @param Servizio $servizio
   * @param string $attachmentType
   * @param string $filename
   * @return JsonResponse
   */
  public function deletePublicAttachmentAction(
    Request $request,
    Servizio $servizio,
    string $attachmentType,
    string $filename
  ): JsonResponse {
    if (!in_array($attachmentType, [PublicFile::CONDITIONS_TYPE, PublicFile::COSTS_TYPE])) {
      $this->logger->error("Invalid type $attachmentType");

      return new JsonResponse(["Invalid type: $attachmentType is not supported"], Response::HTTP_BAD_REQUEST);
    }

    if ($attachmentType === PublicFile::CONDITIONS_TYPE) {
      $attachment = $servizio->getConditionAttachmentByName($filename);
    } elseif ($attachmentType === PublicFile::COSTS_TYPE) {
      $attachment = $servizio->getCostAttachmentByName($filename);
    } else {
      $attachment = null;
    }

    if (!$attachment) {
      return new JsonResponse(["Attachment $filename does not exists"], Response::HTTP_NOT_FOUND);
    }

    try {
      $this->fileService->deleteFilename($attachment->getName(), $servizio, $attachment->getType());
    } catch (FileNotFoundException $e) {
      $this->logger->error("Unable to delete $filename: file not found");
    }

    if ($attachmentType === PublicFile::CONDITIONS_TYPE) {
      $servizio->removeConditionsAttachment($attachment);
    } elseif ($attachmentType === PublicFile::COSTS_TYPE) {
      $servizio->removeCostsAttachment($attachment);
    }

    $this->entityManager->persist($servizio);

    $this->entityManager->flush();

    return new JsonResponse(["$filename deleted successfully"], Response::HTTP_OK);
  }
}



<?php

namespace AppBundle\Controller\Ui\Backend;

use AppBundle\Dto\Application;
use AppBundle\Dto\ApplicationOutcome;
use AppBundle\Entity\Allegato;
use AppBundle\Entity\AllegatoOperatore;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\DematerializedFormPratica;
use AppBundle\Entity\FormIO;
use AppBundle\Entity\Message;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\PraticaRepository;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\StatusChange;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\Form\Operatore\Base\ApplicationOutcomeType;
use AppBundle\Form\Operatore\Base\PraticaOperatoreFlow;
use AppBundle\FormIO\Schema;
use AppBundle\FormIO\SchemaFactory;
use AppBundle\Logging\LogConstants;
use AppBundle\Services\FormServerApiAdapterService;
use AppBundle\Services\InstanceService;
use AppBundle\Services\MailerService;
use AppBundle\Services\Manager\MessageManager;
use AppBundle\Services\Manager\PraticaManager;
use AppBundle\Services\ModuloPdfBuilderService;
use AppBundle\Services\PraticaStatusService;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Flagception\Manager\FeatureManagerInterface;
use JMS\Serializer\Serializer;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * Class OperatoriController
 * @Route("/operatori")
 */
class OperatoriController extends Controller
{

  /** @var SchemaFactory */
  private $schemaFactory;

  /** @var Serializer */
  private $serializer;

  /** @var TranslatorInterface */
  private $translator;

  /** * @var LoggerInterface */
  private $logger;

  /** * @var PraticaStatusService */
  private $praticaStatusService;

  /** @var InstanceService */
  private $instanceService;

  /* @var EntityManagerInterface */
  private $entityManager;
  /**
   * @var FeatureManagerInterface
   */
  private $featureManager;
  /**
   * @var RouterInterface
   */
  private $router;
  /**
   * @var MailerService
   */
  private $mailerService;
  /**
   * @var ModuloPdfBuilderService
   */
  private $moduloPdfBuilderService;
  /**
   * @var PraticaManager
   */
  private $praticaManager;

  /** @var MessageManager */
  private $messageManager;

  /** @var JWTTokenManagerInterface */
  private $JWTTokenManager;
  /**
   * @var FormServerApiAdapterService
   */
  private $formServerService;

  /**
   * OperatoriController constructor.
   * @param SchemaFactory $schemaFactory
   * @param Serializer $serializer
   * @param TranslatorInterface $translator
   * @param LoggerInterface $logger
   * @param PraticaStatusService $praticaStatusService
   * @param InstanceService $instanceService
   * @param EntityManagerInterface $entityManager
   * @param FeatureManagerInterface $featureManager
   * @param RouterInterface $router
   * @param MailerService $mailerService
   * @param ModuloPdfBuilderService $moduloPdfBuilderService
   * @param PraticaManager $praticaManager
   * @param MessageManager $messageManager
   * @param JWTTokenManagerInterface $JWTTokenManager
   * @param FormServerApiAdapterService $formServerService
   */
  public function __construct(
    SchemaFactory $schemaFactory,
    Serializer $serializer,
    TranslatorInterface $translator,
    LoggerInterface $logger,
    PraticaStatusService $praticaStatusService,
    InstanceService $instanceService,
    EntityManagerInterface $entityManager,
    FeatureManagerInterface $featureManager,
    RouterInterface $router,
    MailerService $mailerService,
    ModuloPdfBuilderService $moduloPdfBuilderService,
    PraticaManager $praticaManager,
    MessageManager $messageManager,
    JWTTokenManagerInterface $JWTTokenManager,
    FormServerApiAdapterService $formServerService
  )
  {
    $this->schemaFactory = $schemaFactory;
    $this->serializer = $serializer;
    $this->translator = $translator;
    $this->logger = $logger;
    $this->praticaStatusService = $praticaStatusService;
    $this->instanceService = $instanceService;
    $this->entityManager = $entityManager;
    $this->featureManager = $featureManager;
    $this->router = $router;
    $this->mailerService = $mailerService;
    $this->moduloPdfBuilderService = $moduloPdfBuilderService;
    $this->praticaManager = $praticaManager;
    $this->messageManager = $messageManager;
    $this->JWTTokenManager = $JWTTokenManager;
    $this->formServerService = $formServerService;
  }


  /**
   * @Route("/",name="operatori_index")
   * @return Response
   */
  public function indexAction()
  {
    /** @var OperatoreUser $user */
    $user = $this->getUser();

    /** @var PraticaRepository $praticaRepository */
    $praticaRepository = $this->getDoctrine()->getRepository(Pratica::class);

    $servizi = $this->getDoctrine()->getRepository(Servizio::class)->findBy(
      [
        'id' => $praticaRepository->getServizioIdListByOperatore($user, PraticaRepository::OPERATORI_LOWER_STATE),
      ],
      [
        'name' => 'asc'
      ]
    );

    $result = [];
    /** @var Servizio $s */
    foreach ($servizi as $s) {
      if ($s->getServiceGroup()) {
        $result[$s->getServiceGroup()->getSlug()]['group'] = $s->getServiceGroup();
        $result[$s->getServiceGroup()->getSlug()]['services'][$s->getSlug()] = $s;
      } else {
        $result[$s->getSlug()] = $s;
      }
    }


    $stati = [];
    foreach ($praticaRepository->getStateListByOperatore($user, PraticaRepository::OPERATORI_LOWER_STATE) as $state) {
      $state['name'] = $this->translator->trans($state['name']);
      $stati[] = $state;
    }

    return $this->render( '@App/Operatori/index.html.twig', [
      'servizi' => $result,
      'stati' => $stati,
      'user' => $this->getUser(),
    ]);
  }

  /**
   * @Route("/pratiche",name="operatori_index_json")
   * @param Request $request
   * @return JsonResponse
   */
  public function indexJsonAction(Request $request)
  {
    $limit = intval($request->get('limit', 10));
    $offset = intval($request->get('offset', 0));
    $result = $this->getFilteredPraticheByOperatore($request, $limit, $offset);

    $request->setRequestFormat('json');
    return new JsonResponse(json_encode($result), 200, [], true);
  }

  /**
   * @Route("/pratiche/{servizio}/new", name="new_application_by_operator")
   * @param Request $request
   * @return Response
   */
  public function newAppicationByOperatorAction(Request $request, Servizio $servizio)
  {
    $userId = $request->query->get('user', false);

    $application = new Pratica();
    $application->setServizio($servizio);

    $cpsUserData = false;
    if ($userId) {
      $cpsUserRepo = $this->entityManager->getRepository('AppBundle:CPSUser');
      $cpsUser = $cpsUserRepo->find($userId);
      if ($cpsUser instanceof CPSUser) {
        $application->setUser($cpsUser);
        $schema = $this->schemaFactory->createFromFormId($servizio->getFormIoId());
        $cpsUserData = ['data' => $this->praticaManager->getMappedFormDataWithUserData($schema, $cpsUser)];
      }
    }

    return $this->render( '@App/Operatori/newApplication.html.twig', [
      'formserver_url' => $this->getParameter('formserver_admin_url'),
      'user' => $this->getUser(),
      'token' => $this->JWTTokenManager->create($this->getUser()),
      'service' => $servizio,
      'application' => $application,
      'cps_user_data' => $cpsUserData
    ]);
  }

  /**
   * @Route("/pratiche/csv",name="operatori_index_csv")
   * @param Request $request
   */
  public function indexCSVAction(Request $request)
  {
    $responseCallback = function () use ($request) {

      /** @var OperatoreUser $user */
      $user = $this->getUser();

      /** @var PraticaRepository $praticaRepository */
      $praticaRepository = $this->getDoctrine()->getRepository(Pratica::class);
      $servizi = $this->getDoctrine()->getRepository(Servizio::class)->findBy([
        'id' => $praticaRepository->getServizioIdListByOperatore($user, PraticaRepository::OPERATORI_LOWER_STATE),
      ]);
      $extraHeaders = $request->get('extra_headers', []);
      $handle = fopen('php://output', 'w');
      $result = $this->getFilteredPraticheByOperatore($request, 1, 0);
      $schema = (array)$result['meta']['schema'];

      $csvHeaders = [
        'ID',
        'Numero di protocollo',
        'Login',
        'Pagamenti',
        'Richiedente',
        'Codice fiscale',
        'Data di inserimento',
        'Ultimo cambio stato',
        'Stato',
        'Operatore',
        'Servizio',
      ];
      $extraValues = [];
      foreach ($schema as $item) {
        if (in_array(trim($item['label']), $extraHeaders)) {
          $extraValues[$item['name']] = trim($item['label']);
        }
      }
      $csvHeaders = array_merge($csvHeaders, array_values($extraValues));
      fputcsv($handle, $csvHeaders);

      $dataCount = 0;
      $totalCount = $result['meta']['count'];
      $limit = 100;
      $offset = 0;

      while ($dataCount < $totalCount) {
        $result = $this->getFilteredPraticheByOperatore($request, $limit, $offset);
        $data = $result['data'];
        $dataCount += count($data);
        $offset += $limit;
        foreach ($data as $item) {
          $serviceName = '?';
          foreach ($servizi as $servizio) {
            if ($item['service'] == $servizio->getSlug()) {
              $serviceName = $servizio->getName();
            }
          }
          $csvRow = [
            $item['id'],
            isset($item['protocol_number']) ? $item['protocol_number'] : '',
            $item['idp'],
            $item['is_payment_required'] ? $item['payment_complete'] : '',
            $item['user_name'],
            $item['codice_fiscale'],
            isset($item['submission_time']) ? date('d/m/Y H:i:s', $item['submission_time']) : '',
            isset($item['latest_status_change_time']) ? date('d/m/Y H:i:s', $item['latest_status_change_time']) : '',
            $this->translator->trans('pratica.dettaglio.stato_' . $item['status']),
            $item['operator_name'],
            $serviceName,
          ];

          foreach ($item['data'] as $key => $value) {
            if (isset($extraValues[$key])) {
              $csvRow[$key] = is_array($value) ? count($value) : $value;
            }
          }
          fputcsv($handle, array_values($csvRow));
          flush();
        }
        $this->getDoctrine()->getManager()->clear();
      }

      fclose($handle);
    };

    $fileNameCreationDate = new \DateTime();
    $fileName = 'export_' . $fileNameCreationDate->format('d-m-yy-H-m') . '.csv';
    $response = new StreamedResponse();
    $response->headers->set('Content-Encoding', 'none');
    $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
    $response->headers->set('X-Accel-Buffering', 'no');
    $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $fileName
    ));
    $response->headers->set('Content-Description', 'File Transfer');
    $response->setStatusCode(Response::HTTP_OK);
    $response->setCallback($responseCallback);
    $response->send();
  }

  /**
   * @Route("/pratiche/calculate",name="operatori_index_calculate")
   * @param Request $request
   * @return JsonResponse
   */
  public function indexCalculateAction(Request $request)
  {
    $result = [];
    $functions = [
      'sum' => function (PraticaRepository $praticaRepository, array $fields, OperatoreUser $user, array $parameters) {
        return $praticaRepository->getSumFieldsInPraticheByOperatore(
          $fields,
          $user,
          $parameters
        );
      },
      'avg' => function (PraticaRepository $praticaRepository, array $fields, OperatoreUser $user, array $parameters) {
        return $praticaRepository->getAvgFieldsInPraticheByOperatore(
          $fields,
          $user,
          $parameters
        );
      },
      'count' => function (PraticaRepository $praticaRepository, array $fields, OperatoreUser $user, array $parameters) {
        return $praticaRepository->getCountNotNullFieldsInPraticheByOperatore(
          $fields,
          $user,
          $parameters
        );
      },
    ];
    /** @var PraticaRepository $praticaRepository */
    $praticaRepository = $this->getDoctrine()->getRepository(Pratica::class);
    /** @var OperatoreUser $user */
    $user = $this->getUser();
    $parameters = $this->getPraticheFilters($request);
    $servizioId = $parameters['servizio'];
    if ($servizioId) {
      $servizio = $this->getDoctrine()->getManager()->getRepository(Servizio::class)->findOneBy(['id' => $servizioId]);
      if ($servizio instanceof Servizio) {
        /** @var Schema $schema */
        $schema = $this->schemaFactory->createFromFormId($servizio->getFormIoId());
        foreach ($functions as $name => $callable) {
          $requestFields = $request->get($name, []);
          if (!empty($requestFields)) {
            $fields = [];
            foreach ($requestFields as $requestField) {
              if ($schema->hasComponent($requestField)) {
                $fields[] = $schema->getComponent($requestField);
              }
            }
            if (!empty($fields)) {
              $result[$name] = call_user_func($callable, $praticaRepository, $fields, $user, $parameters);
            }
          }
        }
      }
    }
    $request->setRequestFormat('json');
    return new JsonResponse(json_encode($result), 200, [], true);
  }

  private function getPraticheFilters($request)
  {
    return [
      'gruppo' => $request->get('gruppo', false),
      'servizio' => $request->get('servizio', false),
      'stato' => $request->get('stato', false),
      'workflow' => $request->get('workflow', false),
      'query_field' => $request->get('query_field', false),
      'query' => $request->get('query', false),
      'sort' => $request->get('sort', 'submissionTime'),
      'order' => $request->get('order', 'asc'),
      'collate' => (int)$request->get('collate', false),
      'last_status_change' => (array)$request->get('last_status_change', []),
    ];
  }

  /**
   * @param Request $request
   * @param $limit
   * @param $offset
   * @return array
   * @todo mergiare questa logica in ApplicationsAPIController o in PraticaRepository?
   */
  private function getFilteredPraticheByOperatore($request, $limit, $offset)
  {
    $parameters = $this->getPraticheFilters($request);
    /** @var PraticaRepository $praticaRepository */
    $praticaRepository = $this->getDoctrine()->getRepository(Pratica::class);
    /** @var OperatoreUser $user */
    $user = $this->getUser();

    $filters = [];

    try {
      $count = $praticaRepository->countPraticheByOperatore($user, $parameters);
      /** @var Pratica[] $data */
      $data = $praticaRepository->findPraticheByOperatore($user, $parameters, $limit, $offset);
      $tempParameters = $parameters;
      unset($tempParameters['stato']);
      $tempStates = $praticaRepository->findStatesPraticheByOperatore($user, $tempParameters);
      foreach ($tempStates as $state) {
        $state['name'] = $this->translator->trans($state['name']);
        $filters['states'][] = $state;
      }
    } catch (\Throwable $e) {
      $count = 0;
      $data = [];
      $result['meta']['error'] = true; //$e->getMessage();
    }

    $schema = null;
    $result = [];
    $result['meta']['schema'] = false;
    $servizioId = $parameters['servizio'];
    if ($servizioId && $count > 0) {
      $servizio = $this->getDoctrine()->getManager()->getRepository(Servizio::class)->findOneBy(['id' => $servizioId]);
      if ($servizio instanceof Servizio) {
        $schema = $this->schemaFactory->createFromFormId($servizio->getFormIoId());
        if ($schema->hasComponents()) {
          $result['meta']['schema'] = $schema->getComponents();
        }
      }
    }

    $result['filters'] = $filters;
    $result['meta']['count'] = $count;
    $currentParameters = $parameters;
    $currentParameters['offset'] = $offset;
    $currentParameters['limit'] = $limit;
    $result['meta']['parameter'] = $currentParameters;
    $result['links']['self'] = $this->generateUrl('operatori_index_json', $currentParameters);
    $result['links']['prev'] = null;
    $result['links']['next'] = null;
    if ($offset != 0) {
      $prevParameters = $parameters;
      $prevParameters['offset'] = $offset - $limit;
      $prevParameters['limit'] = $limit;
      $result['links']['prev'] = $this->generateUrl('operatori_index_json', $prevParameters);
    }
    if ($offset + $limit < $count) {
      $nextParameters = $parameters;
      $nextParameters['offset'] = $offset + $limit;
      $nextParameters['limit'] = $limit;
      $result['links']['next'] = $this->generateUrl('operatori_index_json', $nextParameters);
    }

    foreach ($data as $s) {
      //load Application Dto without file collection to reduce the number of db queries
      $application = Application::fromEntity($s, '', false);
      $applicationArray = json_decode($this->serializer->serialize($application, 'json'), true);
      $minimunStatusForAssign = $s->getServizio()->isProtocolRequired() ? Pratica::STATUS_REGISTERED : Pratica::STATUS_SUBMITTED;
      $applicationArray['can_autoassign'] = $s->getOperatore() == null && $s->getStatus() >= $minimunStatusForAssign;
      $applicationArray['is_protocollo_required'] = $s->getServizio()->isProtocolRequired();
      $applicationArray['is_payment_required'] = !empty($s->getPaymentData());
      $applicationArray['payment_complete'] = $s->getStatus() == Pratica::STATUS_PAYMENT_ERROR || $s->getStatus() <= Pratica::STATUS_PAYMENT_OUTCOME_PENDING ? false : true;
      $applicationArray['idp'] = $s->getAuthenticationData()->getAuthenticationMethod() ? $s->getAuthenticationData()->getAuthenticationMethod() : $s->getUser()->getIdp();
      $applicantUser = $s->getUser();
      $codiceFiscale = $applicantUser instanceof CPSUser ? $applicantUser->getCodiceFiscale() : '';
      $codiceFiscaleParts = explode('-', $codiceFiscale);
      $applicationArray['codice_fiscale'] = array_shift($codiceFiscaleParts);
      $applicationArray['operator_name'] = $s->getOperatore() ? $s->getOperatore()->getFullName() : null;
      //@todo check perfomance: children count add one additional db query each result
      $applicationArray['children_count'] = $parameters['collate'] ? $s->getChildren()->count() : null;
      $applicationArray['group'] = $parameters['collate'] && $s->getFolderId() != null ? true : false;

      try {
        $this->checkUserCanAccessPratica($user, $s);
        $applicationArray['can_read'] = true;
      } catch (UnauthorizedHttpException $e) {
        $applicationArray['can_read'] = false;
      }

      if (isset($schema) && $schema->hasComponents() && $s instanceof FormIO) {
        $dematerialized = $s->getDematerializedForms();
        if (isset($dematerialized['data'])) {
          $applicationArray['data'] = $schema->getDataBuilder()->setDataFromArray($dematerialized['data'])->toFullFilledFlatArray();
        } else {
          $applicationArray['data'] = array_fill_keys($schema->getComponentsColumns('name'), '');
        }
      }

      $result['data'][] = $applicationArray;
    }

    return $result;
  }

  /**
   * @Route("/usage",name="operatori_usage")
   * @return Response
   */
  public function usageAction()
  {
    $serviziRepository = $this->getDoctrine()->getRepository('AppBundle:Servizio');
    $servizi = $serviziRepository->findBy(
      [
        'status' => Servizio::STATUS_AVAILABLE
      ]
    );

    $timeZone = date_default_timezone_get();
    $sql = "SELECT COUNT(p.id), date_trunc('year', TO_TIMESTAMP(p.submission_time) AT TIME ZONE '" . $timeZone . "') AS tslot
            FROM pratica AS p WHERE p.status > 1000 and p.submission_time IS NOT NULL GROUP BY tslot ORDER BY tslot ASC";

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

    $statusServices = $this->populateSelectStatusServicesPratiche();
    return $this->render( '@App/Operatori/usage.html.twig', [
      'servizi' => $servizi,
      'pratiche' => $result,
      'user' => $this->getUser(),
      'statusServices' => $statusServices
    ]);
  }

  /**
   * @Route("/{pratica}/protocollo", name="operatori_pratiche_show_protocolli")
   * @param Pratica $pratica
   *
   * @return Response
   * @throws \Exception
   */
  public function showProtocolliAction(Pratica $pratica)
  {
    /** @var OperatoreUser $user */
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($user, $pratica);

    $allegati = [];
    foreach ($pratica->getNumeriProtocollo() as $protocollo) {
      if (Uuid::isValid($protocollo->id)) {
        $allegato = $this->entityManager->getRepository('AppBundle:Allegato')->find($protocollo->id);
      } else {
        $allegato = $this->entityManager->getRepository('AppBundle:Allegato')->findOneBy(['id_documento_protocollo' => $protocollo->id]);
      }
      if ($allegato instanceof Allegato) {
        $allegati[] = [
          'allegato' => $allegato,
          'tipo' => (new \ReflectionClass(get_class($allegato)))->getShortName(),
          'protocollo' => $protocollo->protocollo
        ];
      }
    }

    return $this->render( '@App/Operatori/showProtocolli.html.twig', [
      'pratica' => $pratica,
      'allegati' => $allegati,
      'user' => $user
    ]);
  }

  /**
   * @Route("/parametri-protocollo", name="operatori_impostazioni_protocollo_list")
   * @return array
   */
  public function impostazioniProtocolloListAction()
  {
    return $this->render( '@App/Operatori/impostazioniProtocollo.html.twig', [
      'parameters' => $this->instanceService->getCurrentInstance()->getProtocolloParameters()
    ]);
  }

  /**
   * @Route("/{pratica}/autoassign",name="operatori_autoassing_pratica")
   * @param Pratica $pratica
   *
   * @return RedirectResponse
   * @throws \Exception
   */
  public function autoAssignPraticaAction(Pratica $pratica)
  {
    /** @var OperatoreUser $user */
    $user = $this->getUser();
    try {
      $this->praticaManager->assign($pratica, $user);
    } catch (\Exception $e) {
      $this->addFlash('error', $e->getMessage());
    }

    return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
  }

  /**
   * @Route("/{pratica}/reassign",name="operatori_reassign_pratica")
   * @param Pratica $pratica
   *
   * @return RedirectResponse
   * @throws \Exception
   */
  public function reassignPraticaAction(Pratica $pratica)
  {
    /** @var OperatoreUser $user */
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($user, $pratica);

    try {
      if ($pratica->getOperatore() === null) {
        throw new BadRequestHttpException(
          "La pratica non è assegnata ad alcun operatore"
        );
      }

      if ($pratica->getServizio()->isProtocolRequired() && $pratica->getNumeroProtocollo() === null) {
        throw new BadRequestHttpException("La pratica non ha ancora un numero di protocollo");
      }

      $oldUser = $pratica->getOperatore();
      $pratica->setOperatore($user);
      $this->entityManager->flush($pratica);

      $this->logger->info(
        LogConstants::PRATICA_REASSIGNED,
        [
          'pratica' => $pratica->getId(),
          'user' => $pratica->getUser()->getId(),
          'old_user' => $oldUser->getId(),
        ]
      );
    } catch (\Exception $e) {
      $this->addFlash('error', $e->getMessage());
    }

    return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
  }

  /**
   * @Route("/{pratica}/detail",name="operatori_show_pratica")
   * @param Pratica|DematerializedFormPratica $pratica
   * @param Request $request
   * @return array|RedirectResponse
   */
  public function showPraticaAction(Pratica $pratica, Request $request)
  {
    $translator = $this->translator;

    /** @var OperatoreUser $user */
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($user, $pratica);
    $tab = $request->query->get('tab');

    $attachments = $this->getDoctrine()->getRepository('AppBundle:Pratica')->getMessageAttachments(['author' => $pratica->getUser()->getId()], $pratica);

    /** @var CPSUser $applicant */
    $applicant = $pratica->getUser();

    $messageForm = $this->setupCommentForm($pratica);
    $messageForm->handleRequest($request);
    if ($messageForm->isSubmitted()) {
      // Check if application detail feature is enabled
      if ($this->featureManager->isActive('feature_application_detail')) {
        $visibility = $messageForm->getClickedButton()->getName();

        // Funzionalità non disponibile agli utenti anonimi
        $authData = $pratica->getAuthenticationData();
        if (isset($authData['authenticationMethod']) && $authData['authenticationMethod'] == CPSUser::IDP_NONE && $visibility == Message::VISIBILITY_APPLICANT) {
          $messageForm->addError(new FormError($translator->trans('operatori.messaggi.non_disponibile_anonimo')));
        }

        // E' necessario prendere in carico la pratica per inviare messaggi pubblici
        if (!$pratica->getOperatore() && $visibility == Message::VISIBILITY_APPLICANT) {
          $messageForm->addError(new FormError($translator->trans('operatori.messaggi.prendi_in_carico_per_abilitare')));
        }

        if ($messageForm->isValid()) {
          /** @var Message $message */
          $message = $messageForm->getData();

          $callToActions = [
            ['label' => 'view', 'link' => $this->generateUrl('pratica_show_detail', ['pratica' => $pratica, 'tab' => 'note'], UrlGeneratorInterface::ABSOLUTE_URL)],
            ['label' => 'reply', 'link' => $this->generateUrl('pratica_show_detail', ['pratica' => $pratica, 'tab' => 'note'], UrlGeneratorInterface::ABSOLUTE_URL)],
          ];

          $message->setProtocolRequired(false);
          $message->setVisibility($visibility);
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
          if ($visibility == Message::VISIBILITY_APPLICANT) {
            $this->messageManager->dispatchMailForMessage($message, true);
          }

          return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica, 'tab' => 'note']);
        }

      } else {
        $commento = $messageForm->getData();
        $pratica->addCommento($commento);
        $this->getDoctrine()->getManager()->flush();

        $this->logger->info(
          LogConstants::PRATICA_COMMENTED,
          [
            'pratica' => $pratica->getId(),
            'user' => $pratica->getUser()->getId()
          ]
        );
        return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica, 'tab' => 'note']);
      }
    }

    $outcome = (new ApplicationOutcome())->setApplicationId($pratica->getId());
    $options["helper"] = new TestiAccompagnatoriProcedura($this->translator, $this->getParameter('prefix') . '/' . $this->getParameter('locale'));
    $outcomeForm = $this->createForm(ApplicationOutcomeType::class, $outcome, $options)->handleRequest($request);

    if ($outcomeForm->isSubmitted() && $outcomeForm->isValid()) {

      $allegatoOperatoreRepository = $this->getDoctrine()->getRepository(AllegatoOperatore::class);

      /** @var ApplicationOutcome $outcome */
      $outcome = $outcomeForm->getData();
      $pratica->setEsito($outcome->getOutcome());
      if ($outcome->getMessage() !== null) {
        $pratica->setMotivazioneEsito($outcome->getMessage());
      }
      foreach ($outcome->getAttachments() as $attachment) {
        if (isset($attachment['id'])) {
          $allegatoOperatore = $allegatoOperatoreRepository->findOneBy(['id' => $attachment['id']]);
          if ($allegatoOperatore instanceof AllegatoOperatore) {
            $pratica->addAllegatoOperatore($allegatoOperatore);
          }
        }
      }

      if ($pratica->getServizio()->isPaymentDeferred()) {
        $pratica->setPaymentAmount($outcome->getPaymentAmount());
      }

      try {
        $this->praticaManager->finalize($pratica, $user);
      } catch (\Exception $e) {
        $this->logger->error($e->getMessage() . ' --- ' . $e->getTraceAsString());
        $this->addFlash('error', $e->getMessage());
      }
      return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
    }

    // Integration request
    $integrationRequestform = $this->createFormBuilder(null)
      ->add('message', TextareaType::class, [
        'required' => true,
        'data' => $this->translator->trans('operatori.richiedi_integrazioni_tpl', [
          '%user_name%' => $pratica->getUser()->getFullName(),
          '%servizio%' => $pratica->getServizio()->getName(),
          ]
        ),
        'constraints' => [new NotBlank(), new NotNull()]
      ])
      ->getForm();

    $integrationRequestform->handleRequest($request);
    if ($integrationRequestform->isSubmitted() && $integrationRequestform->isValid()) {

      $data = $integrationRequestform->getData();
      try {
        $this->praticaManager->requestIntegration($pratica, $this->getUser(), $data['message']);
        $this->addFlash('success', 'Integrazione richiesta correttamente.');
      } catch (\Exception $e) {
        $this->logger->error($e->getMessage() . ' --- ' . $e->getTraceAsString());
        $this->addFlash('error', $e->getMessage());
      }

      return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
    }

    /** @var PraticaRepository $repository */
    $repository = $this->getDoctrine()->getRepository('AppBundle:Pratica');
    $praticheRecenti = $repository->findRecentlySubmittedPraticheByUser($pratica, $applicant, 5);

    $fiscalCode = null;
    if ($pratica->getType() == Pratica::TYPE_FORMIO) {
      /** @var Schema $schema */
      $schema = $this->schemaFactory->createFromFormId($pratica->getServizio()->getFormIoId());
      if (!empty($pratica->getDematerializedForms()['data'])) {
        $data = $schema->getDataBuilder()->setDataFromArray($pratica->getDematerializedForms()['data'])->toFullFilledFlatArray();
        if (isset($data['applicant.fiscal_code.fiscal_code'])) {
          $fiscalCode = $data['applicant.fiscal_code.fiscal_code'];
        }
      }
    } else {
      $fiscalCode = $applicant->getCodiceFiscale();
    }

    $moduleProtocols = [];
    $outcomeProtocols = [];

    foreach ($pratica->getNumeriProtocollo() as $protocollo) {
      if (Uuid::isValid($protocollo->id)) {
        $allegato = $this->entityManager->getRepository('AppBundle:Allegato')->find($protocollo->id);
      } else {
        $allegato = $this->entityManager->getRepository('AppBundle:Allegato')->findOneBy(['idDocumentoProtocollo' => $protocollo->id]);
      }
      if ($allegato instanceof Allegato) {
        $moduleProtocols[] = [
          'allegato' => $allegato,
          'tipo' => (new \ReflectionClass(get_class($allegato)))->getShortName(),
          'protocollo' => $protocollo->protocollo,
        ];
      }
    }
    if ($pratica->getRispostaOperatore()) {
      foreach ($pratica->getRispostaOperatore()->getNumeriProtocollo() as $protocollo) {
        $allegato = $this->entityManager->getRepository('AppBundle:Allegato')->find($protocollo->id);
        if ($allegato instanceof Allegato) {
          $outcomeProtocols[] = [
            'allegato' => $allegato,
            'tipo' => (new \ReflectionClass(get_class($allegato)))->getShortName(),
            'protocollo' => $protocollo->protocollo,
          ];
        }
      }
    }

    return $this->render( '@App/Operatori/showPratica.html.twig', [
      'pratiche_recenti' => $praticheRecenti,
      'applications_in_folder' => $repository->getApplicationsInFolder($pratica),
      'messageAttachments' => $attachments,
      'messageForm' => $messageForm->createView(),
      'outcomeForm' => $outcomeForm->createView(),
      'integration_request_form' => $integrationRequestform->createView(),
      'pratica' => $pratica,
      'user' => $this->getUser(),
      'fiscal_code' => $fiscalCode,
      'formserver_url' => $this->getParameter('formserver_admin_url'),
      'tab' => $tab,
      'module_protocols' => $moduleProtocols,
      'outcome_protocols' => $outcomeProtocols,
      'token' => $this->JWTTokenManager->create($this->getUser()),
      'meetings' => $repository->findOrderedMeetings($pratica),
      'incoming_meetings' => $repository->findIncomingMeetings($pratica)
    ]);
  }

  /**
   * @Route("/{pratica}/reopen",name="operatori_show_reopen")
   * @param Pratica|DematerializedFormPratica $pratica
   * @return RedirectResponse
   */
  public function reopenPraticaAction(Pratica $pratica)
  {
    /** @var OperatoreUser $user */
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($user, $pratica);
    if ($pratica->isInFinalStates() && $pratica->getServizio()->isAllowReopening()) {
      try {
        $pratica->setEsito(null);
        $pratica->setMotivazioneEsito(null);
        $pratica->removeRispostaOperatore();

        $statusChange = new StatusChange();
        $statusChange->setEvento('Riapertura pratica');
        $statusChange->setOperatore($user->getFullName());

        $this->praticaStatusService->setNewStatus(
          $pratica,
          Pratica::STATUS_PENDING,
          $statusChange
        );
        $this->addFlash('success', 'Pratica riaperta correttamente');
      } catch (\Exception $e) {
        $this->addFlash('error', 'Si è verificato un errore durante la riapertura della pratica.');
      }
    } else {
      $this->addFlash('error', 'La pratica non può essere riaperta.');
    }
    return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
  }

  /**
   * @Route("/{pratica}/change-status",name="operatori_show_change_status")
   * @param Pratica|DematerializedFormPratica $pratica
   * @return RedirectResponse
   */
  public function changeStatusPraticaAction(Pratica $pratica, Request $request)
  {

    if (!in_array($request->request->get('status'), $pratica->getAllowedStates())) {
      $this->addFlash('error', 'Lo stato selezionato non è tra quelli permessi per la pratica.');
      return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
    }

    $newStatus = $request->request->get('status');

    /** @var OperatoreUser $user */
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($user, $pratica);
    if ($pratica->getServizio()->isAllowReopening()) {
      try {

        if ($pratica->getEsito() !== null && $pratica->getMotivazioneEsito() !== null && $newStatus < Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE ) {
          $pratica->setEsito(null);
          $pratica->setMotivazioneEsito(null);
          $pratica->removeRispostaOperatore();
        }

        // Todo: verificare
        /*if ($pratica->getOperatore() && $newStatus < Pratica::STATUS_PENDING) {
          $pratica->setOperatore(null);
        }*/

        if (!$pratica->getOperatore() && $newStatus >= Pratica::STATUS_PENDING) {
          $pratica->setOperatore($user);
        }

        $statusChange = new StatusChange();
        $statusChange->setEvento('Cambio stato pratica pratica');
        $statusChange->setOperatore($user->getFullName());

        $this->praticaStatusService->setNewStatus(
          $pratica,
          $newStatus,
          $statusChange,
          true
        );
        $this->addFlash('success', 'Stato della pratica cambiato correttamente');
      } catch (\Exception $e) {
        $this->logger->error('Errore durante il cambio stato della pratica: ' . $pratica->getIdDocumentoProtocollo() . ' ' . $e->getMessage());
        $this->addFlash('error', 'Si è verificato un errore durante il cambio stato della pratica.');
      }
    } else {
      $this->addFlash('error', 'Lo stato della pratica non può essere modificato.');
    }
    $this->entityManager->refresh($pratica);
    return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
  }

  /**
   * @Route("/{pratica}/acceptIntegration",name="operatori_accept_integration")
   * @param Pratica|DematerializedFormPratica $pratica
   * @return array|RedirectResponse
   */
  public function acceptIntegrationAction(Pratica $pratica)
  {
    /** @var OperatoreUser $user */
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($user, $pratica);
    if ($pratica->getStatus() === Pratica::STATUS_DRAFT_FOR_INTEGRATION) {
      try {
        $this->praticaManager->acceptIntegration($pratica, $this->getUser());

        $this->addFlash('success', 'Integrazione accettata correttamente');
      } catch (\Exception $e) {
        $this->addFlash('error', 'Si è veritifcato un errore duranre la fase di accettazione');
      }
    } else {
      $this->addFlash('error', 'La pratica non si trova nello corretto');
    }
    return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
  }

  /**
   * @Route("/{pratica}/elabora",name="operatori_elabora_pratica")
   * @param Pratica $pratica
   *
   * @return array|RedirectResponse
   */
  public function elaboraPraticaAction(Pratica $pratica)
  {
    if ($pratica->getStatus() == Pratica::STATUS_COMPLETE || $pratica->getStatus() == Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE) {
      return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
    }

    /** @var OperatoreUser $user */
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($user, $pratica);

    $praticaFlowService = null;
    $praticaFlowServiceName = $pratica->getServizio()->getPraticaFlowOperatoreServiceName();

    if ($praticaFlowServiceName) {
      /** @var PraticaOperatoreFlow $praticaFlowService */
      $praticaFlowService = $this->get($praticaFlowServiceName);
    } else {
      // Default pratica flow
      $praticaFlowService = $this->get('ocsdc.form.flow.standardoperatore');
    }

    $praticaFlowService->setInstanceKey($user->getId());

    $praticaFlowService->bind($pratica);

    if ($pratica->getInstanceId() == null) {
      $pratica->setInstanceId($praticaFlowService->getInstanceId());
    }

    $form = $praticaFlowService->createForm();
    if ($praticaFlowService->isValid($form)) {

      $praticaFlowService->saveCurrentStepData($form);
      $pratica->setLastCompiledStep($praticaFlowService->getCurrentStepNumber());

      if ($praticaFlowService->nextStep()) {
        $this->getDoctrine()->getManager()->flush();
        $form = $praticaFlowService->createForm();
      } else {

        try {
          $this->praticaManager->finalize($pratica, $user);
        } catch (\Exception $e) {
          $this->addFlash('error', $e->getMessage());
        }

        $praticaFlowService->getDataManager()->drop($praticaFlowService);
        $praticaFlowService->reset();

        return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
      }
    }

    return $this->render( '@App/Operatori/elaboraPratica.html.twig', [
      'form' => $form->createView(),
      'pratica' => $praticaFlowService->getFormData(),
      'flow' => $praticaFlowService,
      'user' => $user,
    ]);
  }

  /**
   * @Route("/list",name="operatori_list_by_ente")
   * @Security("has_role('ROLE_OPERATORE_ADMIN')")
   * @return array
   */
  public function listOperatoriByEnteAction()
  {
    $operatoreRepo = $this->getDoctrine()->getRepository('AppBundle:OperatoreUser');
    $operatori = $operatoreRepo->findBy(
      [
        'ente' => $this->getUser()->getEnte(),
      ]
    );
    return $this->render( '@App/Operatori/listOperatoriByEnte.html.twig', [
      'operatori' => $operatori,
      'user' => $this->getUser(),
    ]);
  }

  /**
   * @Route("/detail/{operatore}",name="operatori_detail")
   * @Security("has_role('ROLE_OPERATORE_ADMIN')")
   * @param Request $request
   * @param OperatoreUser $operatore
   * @return array|RedirectResponse
   */
  public function detailOperatoreAction(Request $request, OperatoreUser $operatore)
  {
    /** @var OperatoreUser $user */
    $user = $this->getUser();
    $this->checkUserCanAccessOperatore($user, $operatore);
    $form = $this->setupOperatoreForm($operatore)->handleRequest($request);

    if ($form->isSubmitted()) {
      $data = $form->getData();
      $operatore->setAmbito($data['ambito']);
      $this->getDoctrine()->getManager()->persist($operatore);
      try {
        $this->getDoctrine()->getManager()->flush();
        $this->logger->info(LogConstants::OPERATORE_ADMIN_HAS_CHANGED_OPERATORE_AMBITO, ['operatore_admin' => $this->getUser()->getId(), 'operatore' => $operatore->getId()]);
      } catch (\Exception $e) {
        $this->logger->error($e->getMessage());
      }
      return $this->redirectToRoute('operatori_detail', ['operatore' => $operatore->getId()]);
    }

    return $this->render( '@App/Operatori/detailOperatore.html.twig.twig', [
      'operatore' => $operatore,
      'form' => $form->createView(),
      'user' => $this->getUser(),
    ]);
  }

  /**
   * @Route("/logout", name="logout")
   */
  public function logoutAction()
  {
  }

  /**
   * @param OperatoreUser $operatore
   * @return \Symfony\Component\Form\FormInterface
   */
  private function setupOperatoreForm(OperatoreUser $operatore)
  {
    $formBuilder = $this->createFormBuilder()
      ->add('ambito', TextType::class,
        ['label' => false, 'data' => $operatore->getAmbito(), 'required' => false]
      )
      ->add('save', SubmitType::class,
        ['label' => $this->translator->trans('operatori.profile.salva_modifiche')]
      );
    $form = $formBuilder->getForm();

    return $form;
  }

  /**
   * @return FormInterface
   */
  private function setupCommentForm(Pratica $pratica)
  {
    $data = array();
    $translator = $this->translator;

    if ($this->featureManager->isActive('feature_application_detail')) {
      $message = new Message();
      $message->setApplication($pratica);
      $message->setAuthor($this->getUser());
      $form = $this->createForm('AppBundle\Form\ApplicationMessageType', $message);
    } else {
      $formBuilder = $this->createFormBuilder($data)
        ->add('text', TextareaType::class, [
          'label' => false,
          'required' => true,
          'attr' => [
            'rows' => '5',
            'class' => 'form-control input-inline',
          ],
        ])
        ->add('createdAt', HiddenType::class, ['data' => time()])
        ->add('creator', HiddenType::class, [
          'data' => $this->getUser()->getFullName(),
        ])
        ->add('save', SubmitType::class, [
          'label' => $translator->trans('operatori.aggiungi_commento'),
          'attr' => [
            'class' => 'btn btn-primary',
          ],
        ]);
      $form = $formBuilder->getForm();
    }

    return $form;
  }

  /**
   * @param OperatoreUser $user
   * @param Pratica $pratica
   */
  private function checkUserCanAccessPratica(OperatoreUser $user, Pratica $pratica)
  {
    $isEnabled = in_array($pratica->getServizio()->getId(), $user->getServiziAbilitati()->toArray());
    if (!$isEnabled) {
      throw new UnauthorizedHttpException("User can not read pratica {$pratica->getId()}");
    }
  }

  /**
   * @param OperatoreUser $user
   * @param OperatoreUser $operatore
   */
  private function checkUserCanAccessOperatore(OperatoreUser $user, OperatoreUser $operatore)
  {
    if ($user->getEnte() != $operatore->getEnte()) {
      throw new UnauthorizedHttpException("User can not read operatore {$operatore->getId()}");
    }
  }


  private function populateSelectStatusServicesPratiche()
  {
    /** @var EntityManager $em */
    $em = $this->getDoctrine()->getManager();

    //Servizi, pratiche  delle select di filtraggio
    $serviziPratiche = $em->createQueryBuilder()
      ->select('s.name', 's.slug')
      ->from('AppBundle:Pratica', 'p')
      ->innerJoin('AppBundle:Servizio', 's', 'WITH', 's.id = p.servizio')
      ->distinct()
      ->getQuery()
      ->getResult();

    $sql = "SELECT DISTINCT(status) as status
            FROM pratica WHERE status > 1000 AND submission_time IS NOT NULL ORDER BY status ASC";
    try {
      $em = $this->getDoctrine()->getManager();
      $stmt = $em->getConnection()->prepare($sql);
      $stmt->execute();
      $result = $stmt->fetchAll();
    } catch (DBALException $e) {
      $this->logger->error($e->getMessage());
      $result = [];
    }

    $status = [];
    foreach ($result as $valore) {
      $status[] = array(
        "status" => $valore['status'],
        "name" => $this->translator->trans('pratica.dettaglio.stato_' . $valore['status'])
      );
    }

    return array(
      'statiPratiche' => $status,
      'serviziPratiche' => $serviziPratiche,
    );
  }

  /**
   * @Route("/usage/metriche", name="metriche")
   * @Method("GET")
   * @param Request $request
   * @return Response
   */
  public function metricheAction(Request $request)
  {
    $status = $request->get('status');
    $services = $request->get('services');
    $time = (int)$request->get('time');

    if ($time <= 180) {
      $timeSlot = "minute";
      $timeDiff = "- " . $time . " minutes";
    } elseif ($time <= 1440) {
      $timeSlot = "hour";
      $timeDiff = "- " . ($time / 60) . " hours";
    } else {
      $timeSlot = "day";
      $timeDiff = "- " . ($time / 60 / 24) . " days";
    }

    $timeZone = date_default_timezone_get();

    $calculateInterval = date('Y-m-d H:i:s', strtotime($timeDiff));

    $where = " WHERE p.status > 1000 AND TO_TIMESTAMP(p.submission_time) AT TIME ZONE '" . $timeZone . "' >= '" . $calculateInterval . "'" . "and p.submission_time IS NOT NULL";

    $sqlParams = [];
    if ($services && $services != 'all') {
      $where .= " AND s.slug = ?";
      $sqlParams [] = $services;
    }

    if ($status && $status != 'all') {
      $where .= " AND p.status =" . "'" . (int)$status . "'";
    }

    $sql = "SELECT COUNT(p.id), date_trunc('" . $timeSlot . "', TO_TIMESTAMP(p.submission_time) AT TIME ZONE '" . $timeZone . "') AS tslot, s.name
            FROM pratica AS p LEFT JOIN servizio AS s ON p.servizio_id = s.id" .
      $where .
      " GROUP BY s.name, tslot ORDER BY tslot ASC";

    /** @var EntityManager $em */
    $em = $this->getDoctrine()->getManager();
    try {
      $stmt = $em->getConnection()->executeQuery($sql, $sqlParams);
      $result = $stmt->fetchAllAssociative();
    } catch (Exception $e) {
      $this->logger->error($e->getMessage());
      $result = [];
    }

    $categories = $series = $data = array();

    foreach ($result as $r) {
      if (!in_array($r['tslot'], $categories)) {
        $categories [] = $r['tslot'];
      }
      $series[$r['name']][$r['tslot']] = $r['count'];
    }

    foreach ($series as $k => $v) {
      $temp = [];
      $temp['name'] = $k;
      foreach ($categories as $c) {
        if (isset($v[$c])) {
          $temp['data'][] = $v[$c];
        } else {
          $temp['data'][] = 0;
        }
      }
      $data['series'][] = $temp;
    }
    $data['categories'] = $categories;
    return new Response(json_encode($data), 200);

  }

  /**
   * @Route("/backoffice/{pratica}", name="save_backoffice_data")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @param Request $request
   * @param Pratica $pratica
   *
   * @return Response
   */
  public function saveBackofficeDataAction(Request $request, Pratica $pratica)
  {
    $service = $pratica->getServizio();
    $schema = null;
    $result = $this->formServerService->getFormSchema($service->getBackofficeFormId());
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
      $pratica->setBackofficeFormData($data);
      $this->entityManager->persist($pratica);
      $this->entityManager->flush();

      return new JsonResponse(['status' => 'ok']);

    } catch (\Exception $e) {
      return new JsonResponse(['status' => 'error'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }
}
